<?php

namespace App\Support;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PurchaseOrderManager
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function createOrder(array $payload, User $actor): PurchaseOrder
    {
        return DB::transaction(function () use ($payload, $actor): PurchaseOrder {
            $lines = $this->lineAttributes(Arr::get($payload, 'items', []));
            $totals = $this->totals($lines);

            $order = PurchaseOrder::query()->create(array_merge(
                $this->orderAttributes($payload, $actor),
                $totals,
                [
                    'status' => PurchaseOrder::STATUS_DRAFT,
                    'created_by' => $actor->id,
                ]
            ));

            $order->items()->createMany($lines);

            app(AuditLogger::class)->record('purchase_order.created', $actor, $order, $this->auditMetadata($order));

            return $order->refresh()->load(['supplier', 'items.product']);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateOrder(PurchaseOrder $order, array $payload, User $actor): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $payload, $actor): PurchaseOrder {
            abort_if($order->status === PurchaseOrder::STATUS_CANCELLED, 422, 'Cancelled purchase orders cannot be edited.');

            $lines = $this->lineAttributes(Arr::get($payload, 'items', []));
            $totals = $this->totals($lines);

            $order->update(array_merge($this->orderAttributes($payload, $actor, $order), $totals));

            $order->items()->delete();
            $order->items()->createMany($lines);

            app(AuditLogger::class)->record('purchase_order.updated', $actor, $order, $this->auditMetadata($order));

            return $order->refresh()->load(['supplier', 'items.product']);
        });
    }

    public function markSent(PurchaseOrder $order, User $actor): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $actor): PurchaseOrder {
            abort_if($order->status === PurchaseOrder::STATUS_CANCELLED, 422, 'Cancelled purchase orders cannot be marked as sent.');

            $order->update([
                'status' => PurchaseOrder::STATUS_SENT,
                'sent_at' => now(),
                'sent_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            app(AuditLogger::class)->record('purchase_order.sent', $actor, $order, $this->auditMetadata($order));

            return $order->refresh();
        });
    }

    public function cancelOrder(PurchaseOrder $order, User $actor): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $actor): PurchaseOrder {
            $order->update([
                'status' => PurchaseOrder::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancelled_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            app(AuditLogger::class)->record('purchase_order.cancelled', $actor, $order, $this->auditMetadata($order));

            return $order->refresh();
        });
    }

    public function reopenOrder(PurchaseOrder $order, User $actor): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $actor): PurchaseOrder {
            $order->update([
                'status' => PurchaseOrder::STATUS_DRAFT,
                'sent_at' => null,
                'sent_by' => null,
                'cancelled_at' => null,
                'cancelled_by' => null,
                'updated_by' => $actor->id,
            ]);

            app(AuditLogger::class)->record('purchase_order.reopened', $actor, $order, $this->auditMetadata($order));

            return $order->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function orderAttributes(array $payload, User $actor, ?PurchaseOrder $existingOrder = null): array
    {
        $supplier = Supplier::query()->findOrFail(Arr::get($payload, 'order.supplier_id'));
        $orderNumber = $this->blankToNull(Arr::get($payload, 'order.order_number'));

        if (! $orderNumber) {
            $orderNumber = $existingOrder?->order_number ?: $this->nextOrderNumber();
        }

        return [
            'supplier_id' => $supplier->id,
            'supplier_name_snapshot' => $supplier->name,
            'order_number' => strtoupper($orderNumber),
            'reference_number' => $this->blankToNull(Arr::get($payload, 'order.reference_number')),
            'ordered_on' => $this->blankToNull(Arr::get($payload, 'order.ordered_on')),
            'expected_on' => $this->blankToNull(Arr::get($payload, 'order.expected_on')),
            'payment_terms_days' => $this->blankToNull(Arr::get($payload, 'order.payment_terms_days')),
            'notes' => $this->blankToNull(Arr::get($payload, 'order.notes')),
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
                $unitCost = $this->decimalOrZero(Arr::get($item, 'unit_cost'));
                $discountAmount = $this->decimalOrZero(Arr::get($item, 'discount_amount'));
                $taxRatePercent = $this->decimalOrZero(Arr::get($item, 'tax_rate_percent'));
                $lineTotals = $this->lineTotals($quantity, $unitCost, $discountAmount, $taxRatePercent);

                return array_merge([
                    'product_id' => $product->id,
                    'product_name_snapshot' => $this->blankToNull(Arr::get($item, 'product_name_snapshot')) ?: $product->name,
                    'unit_name' => $this->blankToNull(Arr::get($item, 'unit_name')) ?: $product->baseUnit?->unit_name ?: 'Unit',
                    'quantity' => $quantity,
                    'free_quantity' => $freeQuantity,
                    'unit_cost' => $unitCost,
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
            'subtotal_amount' => $this->formatCents($subtotalCents),
            'discount_amount' => $this->formatCents($discountCents),
            'tax_amount' => $this->formatCents($taxCents),
            'total_amount' => $this->formatCents($totalCents),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function lineTotals(string $quantity, string $unitCost, string $discountAmount, string $taxRatePercent): array
    {
        $quantityMicros = $this->decimalToScaleInt($quantity, 6);
        $unitCostCents = $this->decimalToScaleInt($unitCost, 2);
        $lineSubtotalCents = intdiv(($quantityMicros * $unitCostCents) + 500000, 1000000);
        $discountCents = min($this->decimalToScaleInt($discountAmount, 2), $lineSubtotalCents);
        $taxableCents = max(0, $lineSubtotalCents - $discountCents);
        $taxBasisPoints = $this->decimalToScaleInt($taxRatePercent, 2);
        $lineTaxCents = intdiv(($taxableCents * $taxBasisPoints) + 5000, 10000);
        $lineTotalCents = $taxableCents + $lineTaxCents;

        return [
            'line_subtotal' => $this->formatCents($lineSubtotalCents),
            'line_tax' => $this->formatCents($lineTaxCents),
            'line_total' => $this->formatCents($lineTotalCents),
        ];
    }

    private function nextOrderNumber(): string
    {
        $prefix = 'PO-'.now()->format('Ymd');
        $next = PurchaseOrder::query()
            ->where('order_number', 'like', $prefix.'-%')
            ->count() + 1;

        return $prefix.'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<string, mixed>
     */
    private function auditMetadata(PurchaseOrder $order): array
    {
        return [
            'order_number' => $order->order_number,
            'supplier_id' => $order->supplier_id,
            'status' => $order->status,
            'total_amount' => $order->total_amount,
        ];
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

    private function formatCents(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);

        return sprintf('%s%d.%02d', $sign, intdiv($cents, 100), $cents % 100);
    }

    private function blankToNull(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        return $value === '' ? null : $value;
    }
}
