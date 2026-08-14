<?php

namespace App\Support;

use App\Models\CashDrawerShift;
use App\Models\ControlledMedicineRegisterEntry;
use App\Models\PrescriptionItem;
use App\Models\ProductBatch;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesReturn;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OperationalReportService
{
    /**
     * @return array<string, mixed>
     */
    public function dashboard(string $fromDate, string $toDate): array
    {
        $from = CarbonImmutable::createFromFormat('!Y-m-d', $fromDate)->startOfDay();
        $to = CarbonImmutable::createFromFormat('!Y-m-d', $toDate)->endOfDay();

        $sales = SalesInvoice::query()
            ->where('status', SalesInvoice::STATUS_FINALIZED)
            ->whereBetween('invoice_date', [$fromDate, $toDate]);
        $returns = SalesReturn::query()
            ->where('status', SalesReturn::STATUS_FINALIZED)
            ->whereBetween('return_date', [$fromDate, $toDate]);
        $controlledEntries = ControlledMedicineRegisterEntry::query()
            ->whereBetween('event_date', [$fromDate, $toDate]);
        $cashShifts = CashDrawerShift::query()
            ->whereBetween('opened_at', [$from, $to]);

        $grossSales = $this->sumMoney((clone $sales)->pluck('total_amount'));
        $refunds = $this->sumMoney((clone $returns)->pluck('refund_amount'));

        $trackedRefills = PrescriptionItem::query()
            ->whereNotNull('refill_interval_days')
            ->where('refill_interval_days', '>', 0)
            ->with(['prescription.patient', 'prescription.doctor', 'product'])
            ->get();

        $availableBatches = ProductBatch::query()->where('available_quantity', '>', 0);

        return [
            'period' => [
                'from' => $fromDate,
                'to' => $toDate,
            ],
            'sales' => [
                'bills' => (clone $sales)->count(),
                'cancelled_bills' => SalesInvoice::query()
                    ->where('status', SalesInvoice::STATUS_CANCELLED)
                    ->whereBetween('invoice_date', [$fromDate, $toDate])
                    ->count(),
                'gross_sales' => $grossSales,
                'discounts' => $this->sumMoney((clone $sales)->pluck('discount_amount')),
                'tax' => $this->sumMoney((clone $sales)->pluck('tax_amount')),
                'payment_mix' => (clone $sales)
                    ->select('payment_method', DB::raw('COUNT(*) as bill_count'), DB::raw('SUM(total_amount) as amount'))
                    ->groupBy('payment_method')
                    ->orderByDesc('amount')
                    ->get(),
                'top_products' => SalesInvoiceItem::query()
                    ->select([
                        'product_name_snapshot',
                        DB::raw('SUM(quantity) as quantity'),
                        DB::raw('SUM(line_total) as amount'),
                    ])
                    ->whereHas('salesInvoice', function ($query) use ($fromDate, $toDate): void {
                        $query
                            ->where('status', SalesInvoice::STATUS_FINALIZED)
                            ->whereBetween('invoice_date', [$fromDate, $toDate]);
                    })
                    ->groupBy('product_name_snapshot')
                    ->orderByDesc('amount')
                    ->limit(8)
                    ->get(),
            ],
            'returns' => [
                'documents' => (clone $returns)->count(),
                'refunds' => $refunds,
                'tax' => $this->sumMoney((clone $returns)->pluck('tax_amount')),
            ],
            'inventory' => [
                'available_batches' => (clone $availableBatches)->count(),
                'available_quantity' => $this->sumQuantity((clone $availableBatches)->pluck('available_quantity')),
                'expired_batches' => (clone $availableBatches)->where('expires_on', '<=', today())->count(),
                'next_expiring' => (clone $availableBatches)
                    ->with('product')
                    ->where('expires_on', '>', today())
                    ->orderBy('expires_on')
                    ->limit(10)
                    ->get(),
            ],
            'controlled' => [
                'entries' => (clone $controlledEntries)->count(),
                'dispensed' => (clone $controlledEntries)->where('entry_type', ControlledMedicineRegisterEntry::TYPE_SALE)->count(),
                'reversals' => (clone $controlledEntries)
                    ->whereIn('entry_type', [
                        ControlledMedicineRegisterEntry::TYPE_SALE_CANCEL,
                        ControlledMedicineRegisterEntry::TYPE_SALE_RETURN,
                    ])
                    ->count(),
                'net_quantity' => (clone $controlledEntries)->sum('quantity_effect'),
                'latest' => (clone $controlledEntries)
                    ->orderByDesc('event_date')
                    ->orderByDesc('id')
                    ->limit(12)
                    ->get(),
            ],
            'refills' => [
                'tracked' => $trackedRefills->count(),
                'overdue' => $trackedRefills->filter(fn (PrescriptionItem $item): bool => $item->refillStatus() === PrescriptionItem::REFILL_STATUS_OVERDUE)->count(),
                'due' => $trackedRefills->filter(fn (PrescriptionItem $item): bool => $item->refillStatus() === PrescriptionItem::REFILL_STATUS_DUE)->count(),
                'pending' => $trackedRefills->filter(fn (PrescriptionItem $item): bool => $item->refillStatus() === PrescriptionItem::REFILL_STATUS_PENDING)->count(),
            ],
            'cash_drawer' => [
                'shifts' => (clone $cashShifts)->count(),
                'open_shifts' => (clone $cashShifts)->where('status', CashDrawerShift::STATUS_OPEN)->count(),
                'cash_sales' => $this->sumMoney((clone $cashShifts)->pluck('cash_sales_amount')),
                'refunds' => $this->sumMoney((clone $cashShifts)->pluck('cash_refunds_amount')),
                'variance' => $this->sumMoney((clone $cashShifts)->where('status', CashDrawerShift::STATUS_CLOSED)->pluck('variance_amount')),
            ],
        ];
    }

    public function controlledMedicineCsv(string $fromDate, string $toDate): StreamedResponse
    {
        $filename = 'controlled-medicine-register-'.$fromDate.'-'.$toDate.'.csv';

        return response()->streamDownload(function () use ($fromDate, $toDate): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Event Date',
                'Entry Type',
                'Product',
                'Batch',
                'Patient',
                'Doctor',
                'Prescription',
                'Bill',
                'Return',
                'Quantity Effect',
                'Notes',
            ]);

            ControlledMedicineRegisterEntry::query()
                ->whereBetween('event_date', [$fromDate, $toDate])
                ->orderBy('event_date')
                ->orderBy('id')
                ->cursor()
                ->each(function (ControlledMedicineRegisterEntry $entry) use ($handle): void {
                    fputcsv($handle, [
                        $entry->event_date?->toDateString(),
                        $entry->entryTypeLabel(),
                        $entry->product_name_snapshot,
                        $entry->batch_number_snapshot,
                        $entry->patient_name_snapshot,
                        $entry->doctor_name_snapshot,
                        $entry->prescription_number_snapshot,
                        $entry->invoice_number_snapshot,
                        $entry->return_number_snapshot,
                        $entry->quantity_effect,
                        $entry->notes,
                    ]);
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @param  iterable<mixed>  $values
     */
    private function sumMoney(iterable $values): string
    {
        $cents = 0;

        foreach ($values as $value) {
            $cents += $this->decimalToScaledInt($value, 2);
        }

        return $this->formatScaled($cents, 2);
    }

    /**
     * @param  iterable<mixed>  $values
     */
    private function sumQuantity(iterable $values): string
    {
        $scaled = 0;

        foreach ($values as $value) {
            $scaled += $this->decimalToScaledInt($value, 6);
        }

        return $this->formatScaled($scaled, 6);
    }

    private function decimalToScaledInt(mixed $value, int $scale): int
    {
        $value = trim((string) ($value ?? '0'));
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
}
