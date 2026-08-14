<?php

namespace App\Support;

use App\Models\AuditEvent;
use App\Models\CashDrawerShift;
use App\Models\PrescriptionItem;
use App\Models\ProductBatch;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\StockMovement;
use App\Models\Supplier;
use Carbon\CarbonImmutable;

class DashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $today = today()->toDateString();
        $sales = SalesInvoice::query()->where('status', SalesInvoice::STATUS_FINALIZED)->whereDate('invoice_date', $today);
        $returns = SalesReturn::query()->where('status', SalesReturn::STATUS_FINALIZED)->whereDate('return_date', $today);
        $availableBatches = ProductBatch::query()->where('available_quantity', '>', 0);
        $trackedRefills = PrescriptionItem::query()
            ->whereNotNull('refill_interval_days')
            ->where('refill_interval_days', '>', 0)
            ->with(['prescription.patient', 'product'])
            ->get();

        return [
            'date' => CarbonImmutable::today(),
            'today' => [
                'sales' => $this->sumMoney((clone $sales)->pluck('total_amount')),
                'bills' => (clone $sales)->count(),
                'tax' => $this->sumMoney((clone $sales)->pluck('tax_amount')),
                'returns' => $this->sumMoney((clone $returns)->pluck('refund_amount')),
                'refund_documents' => (clone $returns)->count(),
            ],
            'inventory' => [
                'available_batches' => (clone $availableBatches)->count(),
                'available_quantity' => $this->sumQuantity((clone $availableBatches)->pluck('available_quantity')),
                'expiring_30_days' => (clone $availableBatches)->whereBetween('expires_on', [today(), today()->addDays(30)])->count(),
                'expired' => (clone $availableBatches)->where('expires_on', '<=', today())->count(),
                'blocked' => ProductBatch::query()->where('is_blocked', true)->count(),
            ],
            'attention' => [
                'refills_overdue' => $trackedRefills->filter(fn (PrescriptionItem $item): bool => $item->refillStatus() === PrescriptionItem::REFILL_STATUS_OVERDUE)->count(),
                'refills_due' => $trackedRefills->filter(fn (PrescriptionItem $item): bool => $item->refillStatus() === PrescriptionItem::REFILL_STATUS_DUE)->count(),
                'suppliers_with_balance' => Supplier::query()->where('outstanding_balance', '!=', 0)->count(),
                'supplier_payable' => $this->sumMoney(Supplier::query()->pluck('outstanding_balance')),
                'open_cash_shifts' => CashDrawerShift::query()->where('status', CashDrawerShift::STATUS_OPEN)->count(),
            ],
            'recent_sales' => SalesInvoice::query()
                ->with(['customer', 'items'])
                ->where('status', SalesInvoice::STATUS_FINALIZED)
                ->latest('invoice_date')
                ->latest('id')
                ->limit(8)
                ->get(),
            'recent_movements' => StockMovement::query()
                ->with(['product', 'productBatch'])
                ->latest('occurred_at')
                ->latest('id')
                ->limit(8)
                ->get(),
            'recent_activity' => AuditEvent::query()
                ->with('actor')
                ->latest('occurred_at')
                ->latest('id')
                ->limit(8)
                ->get(),
        ];
    }

    private function sumMoney(iterable $values): string
    {
        $cents = 0;

        foreach ($values as $value) {
            $cents += $this->decimalToScaleInt($value, 2);
        }

        return $this->formatScaled($cents, 2);
    }

    private function sumQuantity(iterable $values): string
    {
        $quantity = 0;

        foreach ($values as $value) {
            $quantity += $this->decimalToScaleInt($value, 6);
        }

        return $this->formatScaled($quantity, 6);
    }

    private function decimalToScaleInt(mixed $value, int $scale): int
    {
        $value = trim((string) ($value ?? '0'));
        $sign = str_starts_with($value, '-') ? -1 : 1;
        $value = ltrim($value, '+-');
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $fraction = str_pad(substr($fraction, 0, $scale), $scale, '0');

        return $sign * (((int) ($whole ?: 0) * (10 ** $scale)) + (int) $fraction);
    }

    private function formatScaled(int $value, int $scale): string
    {
        $sign = $value < 0 ? '-' : '';
        $value = abs($value);
        $base = 10 ** $scale;

        return sprintf('%s%d.%0'.$scale.'d', $sign, intdiv($value, $base), $value % $base);
    }
}
