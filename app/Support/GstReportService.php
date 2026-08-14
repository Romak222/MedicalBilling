<?php

namespace App\Support;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GstReportService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(string $fromDate, string $toDate): array
    {
        $sales = $this->salesRows($fromDate, $toDate);
        $salesReturns = $this->salesReturnRows($fromDate, $toDate);
        $purchases = $this->purchaseRows($fromDate, $toDate);
        $purchaseReturns = $this->purchaseReturnRows($fromDate, $toDate);

        $outputTax = $this->sumMoney($sales, 'tax');
        $outputReversal = $this->sumMoney($salesReturns, 'tax');
        $inputTax = $this->sumMoney($purchases, 'tax');
        $inputReversal = $this->sumMoney($purchaseReturns, 'tax');

        return [
            'period' => ['from' => $fromDate, 'to' => $toDate],
            'sales' => $this->section($sales),
            'sales_returns' => $this->section($salesReturns),
            'purchases' => $this->section($purchases),
            'purchase_returns' => $this->section($purchaseReturns),
            'tax_summary' => [
                'output_tax' => $outputTax,
                'output_reversal' => $outputReversal,
                'net_output_tax' => $this->formatCents($this->cents($outputTax) - $this->cents($outputReversal)),
                'input_tax' => $inputTax,
                'input_reversal' => $inputReversal,
                'net_input_tax' => $this->formatCents($this->cents($inputTax) - $this->cents($inputReversal)),
                'net_tax_payable' => $this->formatCents(
                    ($this->cents($outputTax) - $this->cents($outputReversal)) - ($this->cents($inputTax) - $this->cents($inputReversal))
                ),
            ],
            'rate_rows' => $this->rateRows($sales, $salesReturns, $purchases, $purchaseReturns),
        ];
    }

    public function csv(string $fromDate, string $toDate): StreamedResponse
    {
        $summary = $this->summary($fromDate, $toDate);
        $filename = 'gst-summary-'.$fromDate.'-'.$toDate.'.csv';

        return response()->streamDownload(function () use ($summary): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Report', 'Tax Rate %', 'Document Count', 'Taxable Value', 'Tax Amount']);

            foreach ($summary['rate_rows'] as $row) {
                fputcsv($handle, [$row['report'], $row['rate'], $row['document_count'], $row['taxable'], $row['tax']]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Net Tax Summary', '', '', 'Net Tax Payable', $summary['tax_summary']['net_tax_payable']]);
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array<int, array<string, string|int>> */
    private function salesRows(string $fromDate, string $toDate): array
    {
        return SalesInvoiceItem::query()
            ->with('salesInvoice')
            ->whereHas('salesInvoice', fn ($query) => $query->where('status', SalesInvoice::STATUS_FINALIZED)->whereBetween('invoice_date', [$fromDate, $toDate]))
            ->get()
            ->map(fn (SalesInvoiceItem $item): array => $this->row('Sales', $item->salesInvoice?->invoice_number, $item->salesInvoice?->invoice_date?->toDateString(), $item->tax_rate_percent, $item->line_subtotal, $item->discount_amount, $item->line_tax))
            ->all();
    }

    /** @return array<int, array<string, string|int>> */
    private function salesReturnRows(string $fromDate, string $toDate): array
    {
        return SalesReturnItem::query()
            ->with('salesReturn')
            ->whereHas('salesReturn', fn ($query) => $query->where('status', SalesReturn::STATUS_FINALIZED)->whereBetween('return_date', [$fromDate, $toDate]))
            ->get()
            ->map(fn (SalesReturnItem $item): array => $this->row('Sales Return', $item->salesReturn?->return_number, $item->salesReturn?->return_date?->toDateString(), $item->tax_rate_percent, $item->line_subtotal, $item->discount_amount, $item->line_tax))
            ->all();
    }

    /** @return array<int, array<string, string|int>> */
    private function purchaseRows(string $fromDate, string $toDate): array
    {
        return PurchaseInvoiceItem::query()
            ->with('purchaseInvoice')
            ->whereHas('purchaseInvoice', fn ($query) => $query->where('status', PurchaseInvoice::STATUS_FINALIZED)->whereBetween('invoice_date', [$fromDate, $toDate]))
            ->get()
            ->map(fn (PurchaseInvoiceItem $item): array => $this->row('Purchase', $item->purchaseInvoice?->invoice_number, $item->purchaseInvoice?->invoice_date?->toDateString(), $item->tax_rate_percent, $item->line_subtotal, $item->discount_amount, $item->line_tax))
            ->all();
    }

    /** @return array<int, array<string, string|int>> */
    private function purchaseReturnRows(string $fromDate, string $toDate): array
    {
        return PurchaseReturnItem::query()
            ->with('purchaseReturn')
            ->whereHas('purchaseReturn', fn ($query) => $query->where('status', PurchaseReturn::STATUS_FINALIZED)->whereBetween('return_date', [$fromDate, $toDate]))
            ->get()
            ->map(fn (PurchaseReturnItem $item): array => $this->row('Purchase Return', $item->purchaseReturn?->return_number, $item->purchaseReturn?->return_date?->toDateString(), $item->tax_rate_percent, $item->line_subtotal, $item->discount_amount, $item->line_tax))
            ->all();
    }

    /** @return array<string, string|int> */
    private function row(string $report, ?string $document, ?string $date, mixed $rate, mixed $subtotal, mixed $discount, mixed $tax): array
    {
        return [
            'report' => $report,
            'document' => $document ?: 'Unknown',
            'date' => $date ?: '',
            'rate' => $this->formatRate($rate),
            'taxable' => $this->formatCents(max(0, $this->cents($subtotal) - $this->cents($discount))),
            'tax' => $this->formatCents($this->cents($tax)),
        ];
    }

    /** @param array<int, array<string, string|int>> $rows */
    private function section(array $rows): array
    {
        return [
            'documents' => collect($rows)->pluck('document')->unique()->count(),
            'taxable' => $this->sumMoney($rows, 'taxable'),
            'tax' => $this->sumMoney($rows, 'tax'),
        ];
    }

    /** @param array<int, array<string, string|int>> ...$groups */
    private function rateRows(array ...$groups): array
    {
        $reports = ['Sales', 'Sales Return', 'Purchase', 'Purchase Return'];
        $rows = [];

        foreach ($groups as $index => $group) {
            $byRate = collect($group)->groupBy('rate');

            foreach ($byRate as $rate => $items) {
                $rows[] = [
                    'report' => $reports[$index],
                    'rate' => $rate,
                    'document_count' => $items->pluck('document')->unique()->count(),
                    'taxable' => $this->sumMoney($items, 'taxable'),
                    'tax' => $this->sumMoney($items, 'tax'),
                ];
            }
        }

        return $rows;
    }

    /** @param iterable<array<string, string|int>> $rows */
    private function sumMoney(iterable $rows, string $key): string
    {
        $cents = 0;

        foreach ($rows as $row) {
            $cents += $this->cents($row[$key] ?? '0');
        }

        return $this->formatCents($cents);
    }

    private function cents(mixed $value): int
    {
        return $this->decimalToScaleInt($value, 2);
    }

    private function formatRate(mixed $value): string
    {
        return $this->formatCents($this->decimalToScaleInt($value, 2));
    }

    private function formatCents(int $value): string
    {
        $sign = $value < 0 ? '-' : '';
        $value = abs($value);

        return sprintf('%s%d.%02d', $sign, intdiv($value, 100), $value % 100);
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
}
