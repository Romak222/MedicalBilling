<?php

namespace App\Support;

use App\Models\PrescriptionItem;
use App\Models\ProductBatch;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesReturn;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SalesReturnManager
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function createFinalizedReturn(SalesInvoice $invoice, array $payload, User $actor): SalesReturn
    {
        return DB::transaction(function () use ($invoice, $payload, $actor): SalesReturn {
            $invoice = SalesInvoice::query()
                ->with(['items.salesReturnItems', 'items.productBatch', 'items.prescriptionItem'])
                ->findOrFail($invoice->id);

            abort_unless($invoice->status === SalesInvoice::STATUS_FINALIZED, 422, 'Only finalized sales invoices can be returned.');

            $lines = $this->lineAttributes($invoice, Arr::get($payload, 'items', []));
            abort_if($lines === [], 422, 'Enter a return quantity for at least one bill item.');

            $totals = $this->totals($lines);
            $refundAmount = $this->decimalOrZero(Arr::get($payload, 'return.refund_amount', $totals['total_amount']));
            abort_if(
                $this->decimalToScaleInt($refundAmount, 2) > $this->decimalToScaleInt($totals['total_amount'], 2),
                422,
                'Refund amount cannot exceed the return total.'
            );
            $refundMethod = $this->blankToNull(Arr::get($payload, 'return.refund_method'));
            $cashDrawerShift = strtolower((string) $refundMethod) === 'cash'
                ? app(CashDrawerManager::class)->currentOpen()
                : null;

            $salesReturn = SalesReturn::query()->create(array_merge($totals, [
                'sales_invoice_id' => $invoice->id,
                'return_number' => strtoupper($this->blankToNull(Arr::get($payload, 'return.return_number')) ?: $this->nextReturnNumber()),
                'return_date' => $this->blankToNull(Arr::get($payload, 'return.return_date')) ?: today()->toDateString(),
                'status' => SalesReturn::STATUS_FINALIZED,
                'refund_method' => $refundMethod,
                'cash_drawer_shift_id' => $cashDrawerShift?->id,
                'refund_amount' => $refundAmount,
                'notes' => $this->blankToNull(Arr::get($payload, 'return.notes')),
                'finalized_at' => now(),
                'finalized_by' => $actor->id,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]));

            foreach ($lines as $line) {
                $returnItem = $salesReturn->items()->create($line);

                if ($line['prescription_item_id']) {
                    app(PrescriptionRegistry::class)->decrementDispensedQuantity(
                        PrescriptionItem::query()->findOrFail($line['prescription_item_id']),
                        $line['quantity']
                    );
                }

                app(ControlledMedicineRegister::class)->recordReturnItem($salesReturn, $returnItem, $actor);

                if ($line['restock_to_inventory']) {
                    $batch = ProductBatch::query()->findOrFail($line['product_batch_id']);
                    $batch->update([
                        'available_quantity' => $this->addDecimals($batch->available_quantity, $line['quantity'], 6),
                        'updated_by' => $actor->id,
                    ]);

                    StockMovement::query()->create([
                        'product_id' => $line['product_id'],
                        'product_batch_id' => $line['product_batch_id'],
                        'movement_type' => StockMovement::TYPE_SALE_RETURN_RESTOCK,
                        'source_type' => SalesReturn::class,
                        'source_id' => $salesReturn->id,
                        'quantity' => $line['quantity'],
                        'unit_cost' => $line['unit_price'],
                        'notes' => 'Sales return '.$salesReturn->return_number.' item '.$returnItem->id,
                        'created_by' => $actor->id,
                        'occurred_at' => now(),
                    ]);
                }
            }

            app(AccountingManager::class)->postSalesReturn($salesReturn, $actor);

            app(AuditLogger::class)->record('sales_return.created', $actor, $salesReturn, $this->auditMetadata($salesReturn, $lines));

            return $salesReturn->refresh()->load(['salesInvoice', 'items.product', 'items.productBatch']);
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function lineAttributes(SalesInvoice $invoice, array $items): array
    {
        $invoiceItems = $invoice->items->keyBy('id');

        return collect($items)
            ->map(function (array $item) use ($invoiceItems): ?array {
                $invoiceItemId = (int) Arr::get($item, 'sales_invoice_item_id');
                /** @var SalesInvoiceItem|null $invoiceItem */
                $invoiceItem = $invoiceItems->get($invoiceItemId);
                abort_if(! $invoiceItem, 422, 'Invalid bill item selected for return.');

                $quantity = $this->decimalOrZero(Arr::get($item, 'quantity'));
                $quantityMicros = $this->decimalToScaleInt($quantity, 6);

                if ($quantityMicros <= 0) {
                    return null;
                }

                $soldMicros = $this->decimalToScaleInt($invoiceItem->quantity, 6);
                $returnedMicros = $invoiceItem->salesReturnItems
                    ->sum(fn ($returnItem): int => $this->decimalToScaleInt($returnItem->quantity, 6));
                $remainingMicros = $soldMicros - $returnedMicros;

                abort_if($remainingMicros <= 0, 422, 'This bill item has already been fully returned.');
                abort_if($quantityMicros > $remainingMicros, 422, 'Return quantity exceeds the remaining sold quantity.');

                $lineTotals = $this->returnLineTotals($invoiceItem, $quantityMicros, $remainingMicros);
                $restockToInventory = (bool) Arr::get($item, 'restock_to_inventory', false);

                if ($restockToInventory) {
                    $batch = $invoiceItem->productBatch;

                    abort_if(! $batch, 422, 'Only batch-linked bill items can be restocked.');
                    abort_if($batch->is_blocked, 422, 'Blocked batches cannot be restocked.');
                    abort_if($batch->expires_on === null || ! $batch->expires_on->gt(today()), 422, 'Expired batches cannot be restocked.');
                }

                return array_merge([
                    'sales_invoice_item_id' => $invoiceItem->id,
                    'product_id' => $invoiceItem->product_id,
                    'product_batch_id' => $invoiceItem->product_batch_id,
                    'prescription_item_id' => $invoiceItem->prescription_item_id,
                    'product_name_snapshot' => $invoiceItem->product_name_snapshot,
                    'batch_number_snapshot' => $invoiceItem->batch_number_snapshot,
                    'expires_on_snapshot' => $invoiceItem->expires_on_snapshot,
                    'unit_name' => $invoiceItem->unit_name,
                    'quantity' => $this->formatScaled($quantityMicros, 6),
                    'unit_price' => $invoiceItem->unit_price,
                    'discount_amount' => $lineTotals['discount_amount'],
                    'tax_rate_percent' => $invoiceItem->tax_rate_percent,
                    'restock_to_inventory' => $restockToInventory,
                ], $lineTotals);
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function returnLineTotals(SalesInvoiceItem $invoiceItem, int $quantityMicros, int $remainingMicros): array
    {
        $soldMicros = $this->decimalToScaleInt($invoiceItem->quantity, 6);
        $returnedSubtotalCents = $invoiceItem->salesReturnItems
            ->sum(fn ($returnItem): int => $this->decimalToScaleInt($returnItem->line_subtotal, 2));
        $returnedDiscountCents = $invoiceItem->salesReturnItems
            ->sum(fn ($returnItem): int => $this->decimalToScaleInt($returnItem->discount_amount, 2));
        $returnedTaxCents = $invoiceItem->salesReturnItems
            ->sum(fn ($returnItem): int => $this->decimalToScaleInt($returnItem->line_tax, 2));
        $returnedTotalCents = $invoiceItem->salesReturnItems
            ->sum(fn ($returnItem): int => $this->decimalToScaleInt($returnItem->line_total, 2));

        if ($quantityMicros === $remainingMicros) {
            $lineSubtotalCents = $this->decimalToScaleInt($invoiceItem->line_subtotal, 2) - $returnedSubtotalCents;
            $discountCents = $this->decimalToScaleInt($invoiceItem->discount_amount, 2) - $returnedDiscountCents;
            $taxCents = $this->decimalToScaleInt($invoiceItem->line_tax, 2) - $returnedTaxCents;
            $lineTotalCents = $this->decimalToScaleInt($invoiceItem->line_total, 2) - $returnedTotalCents;
        } else {
            $unitPriceCents = $this->decimalToScaleInt($invoiceItem->unit_price, 2);
            $lineSubtotalCents = intdiv(($quantityMicros * $unitPriceCents) + 500000, 1000000);
            $discountCents = min(
                $this->proportionalCents($invoiceItem->discount_amount, $quantityMicros, $soldMicros),
                $lineSubtotalCents
            );
            $taxCents = $this->proportionalCents($invoiceItem->line_tax, $quantityMicros, $soldMicros);
            $lineTotalCents = max(0, $lineSubtotalCents - $discountCents) + $taxCents;
        }

        return [
            'line_subtotal' => $this->formatScaled(max(0, $lineSubtotalCents), 2),
            'discount_amount' => $this->formatScaled(max(0, $discountCents), 2),
            'line_tax' => $this->formatScaled(max(0, $taxCents), 2),
            'line_total' => $this->formatScaled(max(0, $lineTotalCents), 2),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<string, string>
     */
    private function totals(array $lines): array
    {
        $subtotalCents = 0;
        $discountCents = 0;
        $taxCents = 0;
        $totalCents = 0;

        foreach ($lines as $line) {
            $subtotalCents += $this->decimalToScaleInt($line['line_subtotal'], 2);
            $discountCents += $this->decimalToScaleInt($line['discount_amount'], 2);
            $taxCents += $this->decimalToScaleInt($line['line_tax'], 2);
            $totalCents += $this->decimalToScaleInt($line['line_total'], 2);
        }

        return [
            'subtotal_amount' => $this->formatScaled($subtotalCents, 2),
            'discount_amount' => $this->formatScaled($discountCents, 2),
            'tax_amount' => $this->formatScaled($taxCents, 2),
            'total_amount' => $this->formatScaled($totalCents, 2),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<string, mixed>
     */
    private function auditMetadata(SalesReturn $salesReturn, array $lines): array
    {
        return [
            'return_number' => $salesReturn->return_number,
            'sales_invoice_id' => $salesReturn->sales_invoice_id,
            'refund_method' => $salesReturn->refund_method,
            'cash_drawer_shift_id' => $salesReturn->cash_drawer_shift_id,
            'refund_amount' => $salesReturn->refund_amount,
            'total_amount' => $salesReturn->total_amount,
            'restocked_lines' => collect($lines)->where('restock_to_inventory', true)->count(),
        ];
    }

    private function nextReturnNumber(): string
    {
        $prefix = 'SR-'.now()->format('Ymd');
        $next = SalesReturn::query()
            ->where('return_number', 'like', $prefix.'-%')
            ->count() + 1;

        return $prefix.'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function proportionalCents(mixed $amount, int $quantityMicros, int $soldMicros): int
    {
        if ($soldMicros <= 0) {
            return 0;
        }

        $amountCents = $this->decimalToScaleInt($amount, 2);

        return intdiv(($amountCents * $quantityMicros) + intdiv($soldMicros, 2), $soldMicros);
    }

    private function addDecimals(mixed $left, mixed $right, int $scale = 2): string
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
