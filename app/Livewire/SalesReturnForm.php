<?php

namespace App\Livewire;

use App\Models\SalesInvoice;
use App\Support\SalesReturnManager;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SalesReturnForm extends Component
{
    public ?int $salesInvoiceId = null;

    public bool $refundAmountManual = false;

    public array $return = [
        'return_number' => '',
        'return_date' => '',
        'refund_method' => 'cash',
        'refund_amount' => '',
        'notes' => '',
    ];

    public array $items = [];

    public function mount(SalesInvoice $salesInvoice): void
    {
        abort_unless(auth()->user()?->hasPermission('sales.manage'), 403);
        abort_unless($salesInvoice->status === SalesInvoice::STATUS_FINALIZED, 422, 'Only finalized sales invoices can be returned.');

        $salesInvoice->load(['items.salesReturnItems', 'items.productBatch']);
        abort_if(
            $salesInvoice->items->every(fn ($item): bool => $this->remainingQuantityMicros($item) <= 0),
            422,
            'This sales invoice has no remaining returnable quantity.'
        );

        $this->salesInvoiceId = $salesInvoice->id;
        $this->return['return_date'] = today()->format('Y-m-d');
        $this->fillFromInvoice($salesInvoice);
    }

    public function save(): mixed
    {
        abort_unless(auth()->user()?->hasPermission('sales.manage'), 403);

        $validated = $this->validate();
        $salesReturn = app(SalesReturnManager::class)->createFinalizedReturn(
            SalesInvoice::query()->findOrFail($this->salesInvoiceId),
            $validated,
            auth()->user()
        );

        session()->flash('status', 'Sales return finalized.');

        return $this->redirectRoute('sales-returns.show', $salesReturn, navigate: false);
    }

    public function useRemaining(int $index): void
    {
        $invoice = $this->invoice();
        $invoiceItem = $invoice->items[$index] ?? null;

        if (! $invoiceItem) {
            return;
        }

        $this->items[$index]['quantity'] = $this->formatScaled($this->remainingQuantityMicros($invoiceItem), 6);
    }

    public function updatedReturnRefundAmount(): void
    {
        $this->refundAmountManual = trim((string) $this->return['refund_amount']) !== '';
    }

    public function render()
    {
        $invoice = $this->invoice();
        $previewTotals = $this->previewTotals($invoice);

        if (! $this->refundAmountManual) {
            $this->return['refund_amount'] = number_format($previewTotals['total'], 2, '.', '');
        }

        return view('livewire.sales-return-form', [
            'salesInvoice' => $invoice,
            'previewTotals' => $previewTotals,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'return.return_number' => ['nullable', 'string', 'max:80', Rule::unique('sales_returns', 'return_number')],
            'return.return_date' => ['required', 'date'],
            'return.refund_method' => ['required', 'string', 'max:40'],
            'return.refund_amount' => ['nullable', 'regex:/^\d{1,12}(?:\.\d{1,2})?$/'],
            'return.notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sales_invoice_item_id' => ['required', 'integer', 'exists:sales_invoice_items,id'],
            'items.*.quantity' => ['nullable', 'regex:/^\d{1,5}(?:\.\d{1,6})?$/'],
            'items.*.restock_to_inventory' => ['boolean'],
        ];
    }

    private function invoice(): SalesInvoice
    {
        return SalesInvoice::query()
            ->with(['items.salesReturnItems', 'items.productBatch', 'salesReturns.items'])
            ->findOrFail($this->salesInvoiceId);
    }

    private function fillFromInvoice(SalesInvoice $salesInvoice): void
    {
        $this->items = $salesInvoice->items->map(fn ($item): array => [
            'sales_invoice_item_id' => (string) $item->id,
            'quantity' => '0.000000',
            'restock_to_inventory' => false,
        ])->values()->all();
    }

    /**
     * @return array<string, float>
     */
    private function previewTotals(SalesInvoice $invoice): array
    {
        $subtotal = 0.0;
        $discount = 0.0;
        $tax = 0.0;
        $total = 0.0;

        foreach ($this->items as $index => $item) {
            $invoiceItem = $invoice->items[$index] ?? null;

            if (! $invoiceItem) {
                continue;
            }

            $quantityMicros = min(
                $this->decimalToScaleInt($item['quantity'] ?? '0', 6),
                $this->remainingQuantityMicros($invoiceItem)
            );

            if ($quantityMicros <= 0) {
                continue;
            }

            $remainingMicros = $this->remainingQuantityMicros($invoiceItem);
            $remainingSubtotalCents = $this->decimalToScaleInt($invoiceItem->line_subtotal, 2)
                - $invoiceItem->salesReturnItems->sum(fn ($returnItem): int => $this->decimalToScaleInt($returnItem->line_subtotal, 2));
            $remainingDiscountCents = $this->decimalToScaleInt($invoiceItem->discount_amount, 2)
                - $invoiceItem->salesReturnItems->sum(fn ($returnItem): int => $this->decimalToScaleInt($returnItem->discount_amount, 2));
            $remainingTaxCents = $this->decimalToScaleInt($invoiceItem->line_tax, 2)
                - $invoiceItem->salesReturnItems->sum(fn ($returnItem): int => $this->decimalToScaleInt($returnItem->line_tax, 2));
            $remainingTotalCents = $this->decimalToScaleInt($invoiceItem->line_total, 2)
                - $invoiceItem->salesReturnItems->sum(fn ($returnItem): int => $this->decimalToScaleInt($returnItem->line_total, 2));

            if ($quantityMicros === $remainingMicros) {
                $lineSubtotalCents = $remainingSubtotalCents;
                $lineDiscountCents = $remainingDiscountCents;
                $lineTaxCents = $remainingTaxCents;
                $lineTotalCents = $remainingTotalCents;
            } else {
                $soldMicros = max(1, $this->decimalToScaleInt($invoiceItem->quantity, 6));
                $unitPriceCents = $this->decimalToScaleInt($invoiceItem->unit_price, 2);
                $lineSubtotalCents = intdiv(($quantityMicros * $unitPriceCents) + 500000, 1000000);
                $lineDiscountCents = min(
                    $lineSubtotalCents,
                    intdiv(($this->decimalToScaleInt($invoiceItem->discount_amount, 2) * $quantityMicros) + intdiv($soldMicros, 2), $soldMicros)
                );
                $lineTaxCents = intdiv(($this->decimalToScaleInt($invoiceItem->line_tax, 2) * $quantityMicros) + intdiv($soldMicros, 2), $soldMicros);
                $lineTotalCents = max(0, $lineSubtotalCents - $lineDiscountCents) + $lineTaxCents;
            }

            $subtotal += $lineSubtotalCents / 100;
            $discount += $lineDiscountCents / 100;
            $tax += $lineTaxCents / 100;
            $total += $lineTotalCents / 100;
        }

        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'tax' => round($tax, 2),
            'total' => round($total, 2),
        ];
    }

    private function remainingQuantityMicros(mixed $invoiceItem): int
    {
        return max(
            0,
            $this->decimalToScaleInt($invoiceItem->quantity, 6)
                - $invoiceItem->salesReturnItems->sum(fn ($returnItem): int => $this->decimalToScaleInt($returnItem->quantity, 6))
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

    private function formatScaled(int $value, int $scale): string
    {
        $sign = $value < 0 ? '-' : '';
        $value = abs($value);
        $base = 10 ** $scale;

        return sprintf('%s%d.%0'.$scale.'d', $sign, intdiv($value, $base), $value % $base);
    }
}
