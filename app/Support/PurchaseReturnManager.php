<?php

namespace App\Support;

use App\Models\ProductBatch;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PurchaseReturnManager
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function createFinalizedReturn(PurchaseInvoice $invoice, array $payload, User $actor): PurchaseReturn
    {
        return DB::transaction(function () use ($invoice, $payload, $actor): PurchaseReturn {
            $invoice = PurchaseInvoice::query()
                ->with(['supplier', 'items.productBatch', 'items.purchaseReturnItems'])
                ->findOrFail($invoice->id);

            abort_unless($invoice->status === PurchaseInvoice::STATUS_FINALIZED, 422, 'Only finalized purchase invoices can be returned to a supplier.');
            abort_unless($invoice->supplier_id && $invoice->supplier, 422, 'A supplier is required before returning a purchase invoice.');

            $lines = $this->lineAttributes($invoice, Arr::get($payload, 'items', []));
            abort_if($lines === [], 422, 'Enter a return quantity for at least one received item.');

            $totals = $this->totals($lines);
            $purchaseReturn = PurchaseReturn::query()->create(array_merge($totals, [
                'purchase_invoice_id' => $invoice->id,
                'supplier_id' => $invoice->supplier_id,
                'return_number' => strtoupper($this->blankToNull(Arr::get($payload, 'return.return_number')) ?: $this->nextNumber()),
                'return_date' => $this->blankToNull(Arr::get($payload, 'return.return_date')) ?: today()->toDateString(),
                'status' => PurchaseReturn::STATUS_FINALIZED,
                'reason' => $this->blankToNull(Arr::get($payload, 'return.reason')),
                'notes' => $this->blankToNull(Arr::get($payload, 'return.notes')),
                'finalized_at' => now(),
                'finalized_by' => $actor->id,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]));

            $purchaseReturn->items()->createMany($lines);

            foreach ($lines as $line) {
                $batch = ProductBatch::query()->lockForUpdate()->findOrFail($line['product_batch_id']);
                $returnQuantity = $this->addDecimals($line['quantity'], $line['free_quantity'], 6);

                abort_if(
                    $this->decimalToScaleInt($batch->available_quantity, 6) < $this->decimalToScaleInt($returnQuantity, 6),
                    422,
                    'The available stock is lower than the requested supplier return for '.$line['batch_number'].'.'
                );

                $batch->update([
                    'available_quantity' => $this->addDecimals($batch->available_quantity, '-'.$returnQuantity, 6),
                    'updated_by' => $actor->id,
                ]);

                StockMovement::query()->create([
                    'product_id' => $line['product_id'],
                    'product_batch_id' => $line['product_batch_id'],
                    'movement_type' => StockMovement::TYPE_PURCHASE_RETURN,
                    'source_type' => PurchaseReturn::class,
                    'source_id' => $purchaseReturn->id,
                    'quantity' => '-'.$returnQuantity,
                    'unit_cost' => $line['purchase_rate'],
                    'notes' => 'Purchase return '.$purchaseReturn->return_number,
                    'created_by' => $actor->id,
                    'occurred_at' => now(),
                ]);
            }

            if ($this->cents($purchaseReturn->total_amount) > 0) {
                app(AccountingManager::class)->postPurchaseReturn($purchaseReturn->load('supplier'), $actor);
            }

            app(AuditLogger::class)->record('purchase_return.created', $actor, $purchaseReturn, [
                'return_number' => $purchaseReturn->return_number,
                'purchase_invoice_id' => $purchaseReturn->purchase_invoice_id,
                'supplier_id' => $purchaseReturn->supplier_id,
                'total_amount' => $purchaseReturn->total_amount,
            ]);

            return $purchaseReturn->refresh()->load(['purchaseInvoice', 'supplier', 'items.product', 'items.productBatch', 'journalEntry']);
        });
    }

    public function nextNumber(): string
    {
        $next = (int) PurchaseReturn::query()->max('id') + 1;

        return 'PVR-'.today()->format('Ymd').'-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function lineAttributes(PurchaseInvoice $invoice, array $items): array
    {
        $invoiceItems = $invoice->items->keyBy('id');
        $seen = [];

        return collect($items)
            ->map(function (array $item) use ($invoiceItems, &$seen): ?array {
                $invoiceItemId = (int) Arr::get($item, 'purchase_invoice_item_id');
                $invoiceItem = $invoiceItems->get($invoiceItemId);
                abort_if(! $invoiceItem, 422, 'Invalid purchase invoice item selected for return.');
                abort_if(isset($seen[$invoiceItemId]), 422, 'Each purchase invoice item can appear only once on a return.');
                $seen[$invoiceItemId] = true;

                $quantity = $this->decimalOrZero(Arr::get($item, 'quantity'));
                $freeQuantity = $this->decimalOrZero(Arr::get($item, 'free_quantity'));
                $quantityMicros = $this->decimalToScaleInt($quantity, 6);
                $freeQuantityMicros = $this->decimalToScaleInt($freeQuantity, 6);

                if ($quantityMicros <= 0 && $freeQuantityMicros <= 0) {
                    return null;
                }

                $remainingQuantityMicros = $this->remainingMicros($invoiceItem, 'quantity');
                $remainingFreeQuantityMicros = $this->remainingMicros($invoiceItem, 'free_quantity');
                abort_if($quantityMicros > $remainingQuantityMicros, 422, 'The supplier return quantity exceeds the remaining received quantity.');
                abort_if($freeQuantityMicros > $remainingFreeQuantityMicros, 422, 'The supplier return free quantity exceeds the remaining received quantity.');

                $batch = $invoiceItem->productBatch;
                abort_unless($batch, 422, 'Every returned item must have a received stock batch.');
                abort_unless($invoiceItem->product_id, 422, 'Every returned item must remain linked to a product.');

                $batchAvailableMicros = $this->decimalToScaleInt($batch->available_quantity, 6);
                abort_if($quantityMicros + $freeQuantityMicros > $batchAvailableMicros, 422, 'The supplier return cannot exceed current available stock.');

                $originalQuantityMicros = max(1, $this->decimalToScaleInt($invoiceItem->quantity, 6));
                $subtotalCents = $this->quantityAtRate($quantity, $invoiceItem->purchase_rate);
                $discountCents = $this->proportionalCents(
                    $this->cents($invoiceItem->discount_amount),
                    $quantityMicros,
                    $originalQuantityMicros
                );
                $discountCents = min($discountCents, $subtotalCents);
                $taxableCents = max(0, $subtotalCents - $discountCents);
                $taxBasisPoints = $this->decimalToScaleInt($invoiceItem->tax_rate_percent, 2);
                $taxCents = intdiv(($taxableCents * $taxBasisPoints) + 5000, 10000);

                return [
                    'purchase_invoice_item_id' => $invoiceItem->id,
                    'product_id' => $invoiceItem->product_id,
                    'product_batch_id' => $batch->id,
                    'product_name_snapshot' => $invoiceItem->product_name_snapshot,
                    'unit_name' => $invoiceItem->unit_name,
                    'batch_number' => $invoiceItem->batch_number,
                    'expires_on' => $invoiceItem->expires_on?->toDateString(),
                    'quantity' => $quantity,
                    'free_quantity' => $freeQuantity,
                    'purchase_rate' => $invoiceItem->purchase_rate,
                    'discount_amount' => $this->formatCents($discountCents),
                    'tax_rate_percent' => $invoiceItem->tax_rate_percent,
                    'line_subtotal' => $this->formatCents($subtotalCents),
                    'line_tax' => $this->formatCents($taxCents),
                    'line_total' => $this->formatCents($taxableCents + $taxCents),
                    'notes' => $this->blankToNull(Arr::get($item, 'notes')),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /** @param  array<int, array<string, mixed>>  $lines */
    private function totals(array $lines): array
    {
        $subtotal = 0;
        $discount = 0;
        $tax = 0;
        $total = 0;

        foreach ($lines as $line) {
            $subtotal += $this->cents($line['line_subtotal']);
            $discount += $this->cents($line['discount_amount']);
            $tax += $this->cents($line['line_tax']);
            $total += $this->cents($line['line_total']);
        }

        return [
            'subtotal_amount' => $this->formatCents($subtotal),
            'discount_amount' => $this->formatCents($discount),
            'tax_amount' => $this->formatCents($tax),
            'total_amount' => $this->formatCents($total),
        ];
    }

    private function remainingMicros(mixed $invoiceItem, string $column): int
    {
        $returned = $invoiceItem->purchaseReturnItems->sum(fn ($item): int => $this->decimalToScaleInt($item->{$column}, 6));

        return max(0, $this->decimalToScaleInt($invoiceItem->{$column}, 6) - $returned);
    }

    private function quantityAtRate(string $quantity, mixed $rate): int
    {
        return intdiv(($this->decimalToScaleInt($quantity, 6) * $this->cents($rate)) + 500000, 1000000);
    }

    private function proportionalCents(int $amount, int $quantityMicros, int $baseMicros): int
    {
        if ($quantityMicros >= $baseMicros) {
            return $amount;
        }

        return intdiv(($amount * $quantityMicros) + intdiv($baseMicros, 2), $baseMicros);
    }

    private function addDecimals(mixed $left, mixed $right, int $scale): string
    {
        return $this->formatScaled(
            $this->decimalToScaleInt($left, $scale) + $this->decimalToScaleInt($right, $scale),
            $scale
        );
    }

    private function decimalOrZero(mixed $value): string
    {
        return (string) ($this->blankToNull($value) ?? '0');
    }

    private function cents(mixed $value): int
    {
        return $this->decimalToScaleInt($value, 2);
    }

    private function decimalToScaleInt(mixed $value, int $scale): int
    {
        $value = (string) ($this->blankToNull($value) ?? '0');
        $sign = str_starts_with($value, '-') ? -1 : 1;
        $value = ltrim($value, '+-');
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = str_pad(substr($fraction, 0, $scale), $scale, '0');

        return $sign * (((int) $whole * (10 ** $scale)) + (int) $fraction);
    }

    private function formatCents(int $value): string
    {
        return $this->formatScaled($value, 2);
    }

    private function formatScaled(int $value, int $scale): string
    {
        $sign = $value < 0 ? '-' : '';
        $value = abs($value);
        $base = 10 ** $scale;

        return sprintf('%s%d.%0'.$scale.'d', $sign, intdiv($value, $base), $value % $base);
    }

    private function blankToNull(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        return $value === '' ? null : $value;
    }
}
