<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Support\PurchaseReceivingManager;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PurchaseInvoiceForm extends Component
{
    public ?int $purchaseInvoiceId = null;

    public array $invoice = [
        'supplier_id' => '',
        'purchase_order_id' => '',
        'invoice_number' => '',
        'invoice_date' => '',
        'received_on' => '',
        'notes' => '',
    ];

    public array $items = [];

    public function mount(?PurchaseInvoice $record = null): void
    {
        abort_unless(auth()->user()?->hasPermission('purchases.manage'), 403);

        if ($record?->exists) {
            abort_unless($record->status === PurchaseInvoice::STATUS_DRAFT, 422, 'Only draft purchase invoices can be edited.');
            $this->fillFromInvoice($record->load('items'));
        } else {
            $this->invoice['invoice_date'] = today()->format('Y-m-d');
            $this->invoice['received_on'] = today()->format('Y-m-d');
            $this->items = [$this->blankItem()];
        }
    }

    public function save(): mixed
    {
        abort_unless(auth()->user()?->hasPermission('purchases.manage'), 403);

        $validated = $this->validate();
        $manager = app(PurchaseReceivingManager::class);

        if ($this->purchaseInvoiceId) {
            $invoice = $manager->updateInvoice(PurchaseInvoice::query()->findOrFail($this->purchaseInvoiceId), $validated, auth()->user());
            session()->flash('status', 'Purchase invoice updated.');
        } else {
            $invoice = $manager->createInvoice($validated, auth()->user());
            session()->flash('status', 'Purchase invoice saved as draft.');
        }

        return $this->redirectRoute('purchase-invoices.show', $invoice, navigate: false);
    }

    public function addItem(): void
    {
        $this->items[] = $this->blankItem();
    }

    public function removeItem(int $index): void
    {
        if (count($this->items) === 1) {
            $this->items[0] = $this->blankItem();

            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function useSupplier(): void
    {
        if ($this->invoice['supplier_id'] === '') {
            $this->invoice['purchase_order_id'] = '';
        }
    }

    public function useProduct(int $index): void
    {
        if (! isset($this->items[$index]) || $this->items[$index]['product_id'] === '') {
            return;
        }

        $product = Product::query()->with(['baseUnit', 'taxRate'])->findOrFail($this->items[$index]['product_id']);

        $this->items[$index]['product_name_snapshot'] = $product->name;
        $this->items[$index]['unit_name'] = $product->baseUnit?->unit_name ?: 'Unit';
        $this->items[$index]['tax_rate_percent'] = $product->taxRate?->rate_percent ?? '0.00';
    }

    public function render()
    {
        return view('livewire.purchase-invoice-form', [
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'purchaseOrders' => PurchaseOrder::query()
                ->whereIn('status', [PurchaseOrder::STATUS_DRAFT, PurchaseOrder::STATUS_SENT])
                ->when($this->invoice['supplier_id'] !== '', fn ($query) => $query->where('supplier_id', $this->invoice['supplier_id']))
                ->orderByDesc('ordered_on')
                ->get(['id', 'order_number', 'supplier_id']),
            'products' => Product::query()->with(['baseUnit', 'taxRate'])->where('is_active', true)->orderBy('name')->get(),
            'previewTotals' => $this->previewTotals(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'invoice.supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'invoice.purchase_order_id' => ['nullable', 'integer', 'exists:purchase_orders,id'],
            'invoice.invoice_number' => ['required', 'string', 'max:120', Rule::unique('purchase_invoices', 'invoice_number')->where('supplier_id', $this->invoice['supplier_id'])->ignore($this->purchaseInvoiceId)],
            'invoice.invoice_date' => ['required', 'date'],
            'invoice.received_on' => ['required', 'date'],
            'invoice.notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_name_snapshot' => ['required', 'string', 'max:180'],
            'items.*.unit_name' => ['required', 'string', 'max:80'],
            'items.*.batch_number' => ['required', 'string', 'max:120'],
            'items.*.manufactured_on' => ['nullable', 'date'],
            'items.*.expires_on' => ['required', 'date', 'after:today'],
            'items.*.quantity' => ['required', 'regex:/^(?!0+(?:\.0+)?$)\d{1,5}(?:\.\d{1,6})?$/'],
            'items.*.free_quantity' => ['nullable', 'regex:/^\d{1,5}(?:\.\d{1,6})?$/'],
            'items.*.mrp' => ['required', 'regex:/^(?!0+(?:\.0+)?$)\d{1,7}(?:\.\d{1,2})?$/'],
            'items.*.purchase_rate' => ['required', 'regex:/^(?!0+(?:\.0+)?$)\d{1,7}(?:\.\d{1,2})?$/'],
            'items.*.sale_rate' => ['nullable', 'regex:/^\d{1,7}(?:\.\d{1,2})?$/'],
            'items.*.discount_amount' => ['nullable', 'regex:/^\d{1,10}(?:\.\d{1,2})?$/'],
            'items.*.tax_rate_percent' => ['nullable', 'regex:/^\d{1,3}(?:\.\d{1,2})?$/'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function blankItem(): array
    {
        return [
            'product_id' => '',
            'product_name_snapshot' => '',
            'unit_name' => '',
            'batch_number' => '',
            'manufactured_on' => '',
            'expires_on' => '',
            'quantity' => '1',
            'free_quantity' => '0',
            'mrp' => '0.00',
            'purchase_rate' => '0.00',
            'sale_rate' => '',
            'discount_amount' => '0.00',
            'tax_rate_percent' => '0.00',
            'notes' => '',
        ];
    }

    private function fillFromInvoice(PurchaseInvoice $invoice): void
    {
        $this->purchaseInvoiceId = $invoice->id;
        $this->invoice = [
            'supplier_id' => $invoice->supplier_id ? (string) $invoice->supplier_id : '',
            'purchase_order_id' => $invoice->purchase_order_id ? (string) $invoice->purchase_order_id : '',
            'invoice_number' => $invoice->invoice_number,
            'invoice_date' => $invoice->invoice_date?->format('Y-m-d') ?? today()->format('Y-m-d'),
            'received_on' => $invoice->received_on?->format('Y-m-d') ?? today()->format('Y-m-d'),
            'notes' => $invoice->notes ?? '',
        ];
        $this->items = $invoice->items->map(fn ($item): array => [
            'product_id' => $item->product_id ? (string) $item->product_id : '',
            'product_name_snapshot' => $item->product_name_snapshot,
            'unit_name' => $item->unit_name,
            'batch_number' => $item->batch_number,
            'manufactured_on' => $item->manufactured_on?->format('Y-m-d') ?? '',
            'expires_on' => $item->expires_on?->format('Y-m-d') ?? '',
            'quantity' => $item->quantity,
            'free_quantity' => $item->free_quantity,
            'mrp' => $item->mrp,
            'purchase_rate' => $item->purchase_rate,
            'sale_rate' => $item->sale_rate ?? '',
            'discount_amount' => $item->discount_amount,
            'tax_rate_percent' => $item->tax_rate_percent,
            'notes' => $item->notes ?? '',
        ])->values()->all();

        if ($this->items === []) {
            $this->items = [$this->blankItem()];
        }
    }

    /**
     * @return array<string, float>
     */
    private function previewTotals(): array
    {
        $subtotal = 0.0;
        $discount = 0.0;
        $tax = 0.0;
        $total = 0.0;

        foreach ($this->items as $item) {
            $lineSubtotal = max(0, (float) ($item['quantity'] ?? 0)) * max(0, (float) ($item['purchase_rate'] ?? 0));
            $lineDiscount = min(max(0, (float) ($item['discount_amount'] ?? 0)), $lineSubtotal);
            $lineTaxable = max(0, $lineSubtotal - $lineDiscount);
            $lineTax = $lineTaxable * (max(0, (float) ($item['tax_rate_percent'] ?? 0)) / 100);

            $subtotal += $lineSubtotal;
            $discount += $lineDiscount;
            $tax += $lineTax;
            $total += $lineTaxable + $lineTax;
        }

        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'tax' => round($tax, 2),
            'total' => round($total, 2),
        ];
    }
}
