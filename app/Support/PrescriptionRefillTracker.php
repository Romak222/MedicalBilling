<?php

namespace App\Support;

use App\Models\PrescriptionItem;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;

class PrescriptionRefillTracker
{
    public function sync(PrescriptionItem|int $prescriptionItem): PrescriptionItem
    {
        $item = $prescriptionItem instanceof PrescriptionItem
            ? $prescriptionItem->loadMissing([
                'prescription',
                'salesInvoiceItems.salesInvoice',
                'salesInvoiceItems.salesReturnItems',
            ])
            : PrescriptionItem::query()
                ->with([
                    'prescription',
                    'salesInvoiceItems.salesInvoice',
                    'salesInvoiceItems.salesReturnItems',
                ])
                ->findOrFail($prescriptionItem);

        $latestActiveDispense = collect($item->salesInvoiceItems)
            ->filter(function (SalesInvoiceItem $salesInvoiceItem): bool {
                return $salesInvoiceItem->salesInvoice?->status === SalesInvoice::STATUS_FINALIZED
                    && $this->netDispensedMicros($salesInvoiceItem) > 0;
            })
            ->sortByDesc(function (SalesInvoiceItem $salesInvoiceItem): string {
                return ($salesInvoiceItem->salesInvoice?->invoice_date?->toDateString() ?? '0000-00-00')
                    .'|'.str_pad((string) $salesInvoiceItem->id, 10, '0', STR_PAD_LEFT);
            })
            ->first();

        $lastDispensedOn = $latestActiveDispense?->salesInvoice?->invoice_date?->toDateString();
        $nextRefillDueOn = null;

        if (
            $lastDispensedOn
            && $item->refillTrackingEnabled()
            && $item->remainingQuantityMicros() > 0
        ) {
            $nextRefillDueOn = $latestActiveDispense->salesInvoice->invoice_date
                ->copy()
                ->addDays((int) $item->refill_interval_days)
                ->toDateString();
        }

        $item->update([
            'last_dispensed_on' => $lastDispensedOn,
            'next_refill_due_on' => $nextRefillDueOn,
        ]);

        return $item->refresh()->loadMissing('prescription');
    }

    private function netDispensedMicros(SalesInvoiceItem $salesInvoiceItem): int
    {
        return max(
            0,
            $this->decimalToScaleInt($salesInvoiceItem->quantity, 6)
                - $salesInvoiceItem->salesReturnItems->sum(
                    fn ($returnItem): int => $this->decimalToScaleInt($returnItem->quantity, 6)
                )
        );
    }

    private function decimalToScaleInt(mixed $value, int $scale): int
    {
        $value = trim((string) ($value ?? '0'));
        $sign = str_starts_with($value, '-') ? -1 : 1;
        $value = ltrim($value, '+-');
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = str_pad(substr($fraction, 0, $scale), $scale, '0');

        return $sign * (((int) $whole * (10 ** $scale)) + (int) $fraction);
    }
}
