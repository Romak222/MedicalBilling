<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PurchaseReceivingManager
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function createInvoice(array $payload, User $actor): PurchaseInvoice
    {
        return DB::transaction(function () use ($payload, $actor): PurchaseInvoice {
            $lines = $this->lineAttributes(Arr::get($payload, 'items', []));
            $totals = $this->totals($lines);

            $invoice = PurchaseInvoice::query()->create(array_merge(
                $this->invoiceAttributes($payload, $actor),
                $totals,
                [
                    'status' => PurchaseInvoice::STATUS_DRAFT,
                    'created_by' => $actor->id,
                ]
            ));

            $invoice->items()->createMany($lines);

            app(AuditLogger::class)->record('purchase_invoice.created', $actor, $invoice, $this->auditMetadata($invoice));

            return $invoice->refresh()->load(['supplier', 'purchaseOrder', 'items.product']);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateInvoice(PurchaseInvoice $invoice, array $payload, User $actor): PurchaseInvoice
    {
        return DB::transaction(function () use ($invoice, $payload, $actor): PurchaseInvoice {
            abort_unless($invoice->status === PurchaseInvoice::STATUS_DRAFT, 422, 'Only draft purchase invoices can be edited.');

            $lines = $this->lineAttributes(Arr::get($payload, 'items', []));
            $totals = $this->totals($lines);

            $invoice->update(array_merge($this->invoiceAttributes($payload, $actor, $invoice), $totals));
            $invoice->items()->delete();
            $invoice->items()->createMany($lines);

            app(AuditLogger::class)->record('purchase_invoice.updated', $actor, $invoice, $this->auditMetadata($invoice));

            return $invoice->refresh()->load(['supplier', 'purchaseOrder', 'items.product']);
        });
    }

    public function finalizeInvoice(PurchaseInvoice $invoice, User $actor): PurchaseInvoice
    {
        return DB::transaction(function () use ($invoice, $actor): PurchaseInvoice {
            abort_unless($invoice->status === PurchaseInvoice::STATUS_DRAFT, 422, 'Only draft purchase invoices can be finalized.');
            $invoice->load('items');

            foreach ($invoice->items as $item) {
                $batch = ProductBatch::query()->firstOrCreate(
                    [
                        'product_id' => $item->product_id,
                        'batch_number' => strtoupper($item->batch_number),
                    ],
                    [
                        'manufactured_on' => $item->manufactured_on,
                        'expires_on' => $item->expires_on,
                        'mrp' => $item->mrp,
                        'purchase_rate' => $item->purchase_rate,
                        'sale_rate' => $item->sale_rate,
                        'available_quantity' => '0',
                        'is_blocked' => false,
                        'created_by' => $actor->id,
                        'updated_by' => $actor->id,
                    ]
                );

                $batch->update([
                    'manufactured_on' => $item->manufactured_on,
                    'expires_on' => $item->expires_on,
                    'mrp' => $item->mrp,
                    'purchase_rate' => $item->purchase_rate,
                    'sale_rate' => $item->sale_rate,
                    'available_quantity' => $this->addDecimals($batch->available_quantity, $this->addDecimals($item->quantity, $item->free_quantity, 6), 6),
                    'updated_by' => $actor->id,
                ]);

                $item->update(['product_batch_id' => $batch->id]);

                StockMovement::query()->create([
                    'product_id' => $item->product_id,
                    'product_batch_id' => $batch->id,
                    'movement_type' => StockMovement::TYPE_PURCHASE_RECEIVE,
                    'source_type' => PurchaseInvoice::class,
                    'source_id' => $invoice->id,
                    'quantity' => $this->addDecimals($item->quantity, $item->free_quantity, 6),
                    'unit_cost' => $item->purchase_rate,
                    'notes' => 'Purchase invoice '.$invoice->invoice_number,
                    'created_by' => $actor->id,
                    'occurred_at' => now(),
                ]);
            }

            $invoice->update([
                'status' => PurchaseInvoice::STATUS_FINALIZED,
                'finalized_at' => now(),
                'finalized_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            app(AuditLogger::class)->record('purchase_invoice.finalized', $actor, $invoice, $this->auditMetadata($invoice));

            return $invoice->refresh()->load(['supplier', 'purchaseOrder', 'items.productBatch', 'items.product']);
        });
    }

    public function cancelDraft(PurchaseInvoice $invoice, User $actor): PurchaseInvoice
    {
        return DB::transaction(function () use ($invoice, $actor): PurchaseInvoice {
            abort_unless($invoice->status === PurchaseInvoice::STATUS_DRAFT, 422, 'Only draft purchase invoices can be cancelled.');

            $invoice->update([
                'status' => PurchaseInvoice::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancelled_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            app(AuditLogger::class)->record('purchase_invoice.cancelled', $actor, $invoice, $this->auditMetadata($invoice));

            return $invoice->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function invoiceAttributes(array $payload, User $actor, ?PurchaseInvoice $existingInvoice = null): array
    {
        $supplier = Supplier::query()->findOrFail(Arr::get($payload, 'invoice.supplier_id'));
        $purchaseOrderId = $this->blankToNull(Arr::get($payload, 'invoice.purchase_order_id'));

        if ($purchaseOrderId) {
            PurchaseOrder::query()->findOrFail($purchaseOrderId);
        }

        return [
            'supplier_id' => $supplier->id,
            'purchase_order_id' => $purchaseOrderId,
            'supplier_name_snapshot' => $supplier->name,
            'invoice_number' => strtoupper($this->blankToNull(Arr::get($payload, 'invoice.invoice_number'))),
            'invoice_date' => $this->blankToNull(Arr::get($payload, 'invoice.invoice_date')),
            'received_on' => $this->blankToNull(Arr::get($payload, 'invoice.received_on')),
            'notes' => $this->blankToNull(Arr::get($payload, 'invoice.notes')),
            'updated_by' => $actor->id,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function lineAttributes(array $items): array
    {
        return collect($items)
            ->map(function (array $item): array {
                $product = Product::query()->with(['baseUnit', 'taxRate'])->findOrFail(Arr::get($item, 'product_id'));
                $quantity = $this->decimalOrZero(Arr::get($item, 'quantity'));
                $freeQuantity = $this->decimalOrZero(Arr::get($item, 'free_quantity'));
                $purchaseRate = $this->decimalOrZero(Arr::get($item, 'purchase_rate'));
                $discountAmount = $this->decimalOrZero(Arr::get($item, 'discount_amount'));
                $taxRatePercent = $this->decimalOrZero(Arr::get($item, 'tax_rate_percent'));
                $lineTotals = $this->lineTotals($quantity, $purchaseRate, $discountAmount, $taxRatePercent);

                return array_merge([
                    'product_id' => $product->id,
                    'product_name_snapshot' => $this->blankToNull(Arr::get($item, 'product_name_snapshot')) ?: $product->name,
                    'unit_name' => $this->blankToNull(Arr::get($item, 'unit_name')) ?: $product->baseUnit?->unit_name ?: 'Unit',
                    'batch_number' => strtoupper($this->blankToNull(Arr::get($item, 'batch_number'))),
                    'manufactured_on' => $this->blankToNull(Arr::get($item, 'manufactured_on')),
                    'expires_on' => $this->blankToNull(Arr::get($item, 'expires_on')),
                    'quantity' => $quantity,
                    'free_quantity' => $freeQuantity,
                    'mrp' => $this->decimalOrZero(Arr::get($item, 'mrp')),
                    'purchase_rate' => $purchaseRate,
                    'sale_rate' => $this->moneyOrNull(Arr::get($item, 'sale_rate')),
                    'discount_amount' => $discountAmount,
                    'tax_rate_percent' => $taxRatePercent,
                    'notes' => $this->blankToNull(Arr::get($item, 'notes')),
                ], $lineTotals);
            })
            ->values()
            ->all();
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
            $discountCents += min(
                $this->decimalToScaleInt($line['discount_amount'], 2),
                $this->decimalToScaleInt($line['line_subtotal'], 2)
            );
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
     * @return array<string, string>
     */
    private function lineTotals(string $quantity, string $purchaseRate, string $discountAmount, string $taxRatePercent): array
    {
        $quantityMicros = $this->decimalToScaleInt($quantity, 6);
        $purchaseRateCents = $this->decimalToScaleInt($purchaseRate, 2);
        $lineSubtotalCents = intdiv(($quantityMicros * $purchaseRateCents) + 500000, 1000000);
        $discountCents = min($this->decimalToScaleInt($discountAmount, 2), $lineSubtotalCents);
        $taxableCents = max(0, $lineSubtotalCents - $discountCents);
        $taxBasisPoints = $this->decimalToScaleInt($taxRatePercent, 2);
        $lineTaxCents = intdiv(($taxableCents * $taxBasisPoints) + 5000, 10000);
        $lineTotalCents = $taxableCents + $lineTaxCents;

        return [
            'line_subtotal' => $this->formatScaled($lineSubtotalCents, 2),
            'line_tax' => $this->formatScaled($lineTaxCents, 2),
            'line_total' => $this->formatScaled($lineTotalCents, 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function auditMetadata(PurchaseInvoice $invoice): array
    {
        return [
            'invoice_number' => $invoice->invoice_number,
            'supplier_id' => $invoice->supplier_id,
            'purchase_order_id' => $invoice->purchase_order_id,
            'status' => $invoice->status,
            'total_amount' => $invoice->total_amount,
        ];
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

    private function moneyOrNull(mixed $value): ?string
    {
        $value = $this->blankToNull($value);

        return $value === null ? null : (string) $value;
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
