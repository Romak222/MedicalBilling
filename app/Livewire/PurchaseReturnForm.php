<?php

namespace App\Livewire;

use App\Models\PurchaseInvoice;
use App\Support\PurchaseReturnManager;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PurchaseReturnForm extends Component
{
    public ?int $purchaseInvoiceId = null;

    public array $return = [
        'return_number' => '',
        'return_date' => '',
        'reason' => '',
        'notes' => '',
    ];

    public array $items = [];

    public function mount(PurchaseInvoice $purchaseInvoice): void
    {
        abort_unless(auth()->user()?->hasPermission('purchases.manage'), 403);
        abort_unless($purchaseInvoice->status === PurchaseInvoice::STATUS_FINALIZED, 422, 'Only finalized purchase invoices can be returned to a supplier.');

        $this->purchaseInvoiceId = $purchaseInvoice->id;
        $this->return['return_date'] = today()->toDateString();
        $this->fillFromInvoice($purchaseInvoice->load(['supplier', 'items.purchaseReturnItems', 'items.productBatch']));
    }

    public function save(): mixed
    {
        abort_unless(auth()->user()?->hasPermission('purchases.manage'), 403);

        $validated = $this->validate();
        $purchaseReturn = app(PurchaseReturnManager::class)->createFinalizedReturn(
            PurchaseInvoice::query()->findOrFail($this->purchaseInvoiceId),
            $validated,
            auth()->user()
        );

        session()->flash('status', 'Purchase return finalized and stock removed.');

        return $this->redirectRoute('purchase-returns.show', $purchaseReturn, navigate: false);
    }

    public function useRemaining(int $index): void
    {
        $invoice = $this->invoice();
        $invoiceItem = $invoice->items[$index] ?? null;

        if (! $invoiceItem) {
            return;
        }

        $this->items[$index]['quantity'] = $this->formatScaled($this->remainingMicros($invoiceItem, 'quantity'), 6);
        $this->items[$index]['free_quantity'] = $this->formatScaled($this->remainingMicros($invoiceItem, 'free_quantity'), 6);
    }

    public function render()
    {
        $invoice = $this->invoice();

        return view('livewire.purchase-return-form', [
            'purchaseInvoice' => $invoice,
            'previewTotals' => $this->previewTotals($invoice),
        ]);
    }

    protected function rules(): array
    {
        return [
            'return.return_number' => ['nullable', 'string', 'max:80', Rule::unique('purchase_returns', 'return_number')],
            'return.return_date' => ['required', 'date'],
            'return.reason' => ['nullable', 'string', 'max:255'],
            'return.notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_invoice_item_id' => ['required', 'integer', 'exists:purchase_invoice_items,id'],
            'items.*.quantity' => ['nullable', 'regex:/^\d{1,5}(?:\.\d{1,6})?$/'],
            'items.*.free_quantity' => ['nullable', 'regex:/^\d{1,5}(?:\.\d{1,6})?$/'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function invoice(): PurchaseInvoice
    {
        return PurchaseInvoice::query()
            ->with(['supplier', 'items.purchaseReturnItems', 'items.productBatch'])
            ->findOrFail($this->purchaseInvoiceId);
    }

    private function fillFromInvoice(PurchaseInvoice $invoice): void
    {
        $this->items = $invoice->items->map(fn ($item): array => [
            'purchase_invoice_item_id' => (string) $item->id,
            'quantity' => '0.000000',
            'free_quantity' => '0.000000',
            'notes' => '',
        ])->values()->all();
    }

    /** @return array<string, string> */
    private function previewTotals(PurchaseInvoice $invoice): array
    {
        $subtotal = 0;
        $tax = 0;

        foreach ($invoice->items as $index => $invoiceItem) {
            $quantityMicros = $this->decimalToScaleInt($this->items[$index]['quantity'] ?? '0', 6);
            if ($quantityMicros <= 0) {
                continue;
            }

            $lineSubtotal = intdiv(($quantityMicros * $this->cents($invoiceItem->purchase_rate)) + 500000, 1000000);
            $subtotal += $lineSubtotal;
            $tax += intdiv(($lineSubtotal * $this->decimalToScaleInt($invoiceItem->tax_rate_percent, 2)) + 5000, 10000);
        }

        return [
            'subtotal' => $this->formatCents($subtotal),
            'tax' => $this->formatCents($tax),
            'total' => $this->formatCents($subtotal + $tax),
        ];
    }

    private function remainingMicros(mixed $invoiceItem, string $column): int
    {
        $returned = $invoiceItem->purchaseReturnItems->sum(fn ($item): int => $this->decimalToScaleInt($item->{$column}, 6));

        return max(0, $this->decimalToScaleInt($invoiceItem->{$column}, 6) - $returned);
    }

    private function cents(mixed $value): int
    {
        return $this->decimalToScaleInt($value, 2);
    }

    private function decimalToScaleInt(mixed $value, int $scale): int
    {
        $value = (string) ($value ?? '0');
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) $whole * (10 ** $scale)) + (int) str_pad(substr($fraction, 0, $scale), $scale, '0');
    }

    private function formatCents(int $value): string
    {
        return $this->formatScaled($value, 2);
    }

    private function formatScaled(int $value, int $scale): string
    {
        $base = 10 ** $scale;

        return sprintf('%d.%0'.$scale.'d', intdiv($value, $base), $value % $base);
    }
}
