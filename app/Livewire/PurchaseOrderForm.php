<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Support\PurchaseOrderManager;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PurchaseOrderForm extends Component
{
    public ?int $purchaseOrderId = null;

    public array $order = [
        'supplier_id' => '',
        'order_number' => '',
        'reference_number' => '',
        'ordered_on' => '',
        'expected_on' => '',
        'payment_terms_days' => '',
        'notes' => '',
    ];

    public array $items = [];

    public function mount(?PurchaseOrder $record = null): void
    {
        abort_unless(auth()->user()?->hasPermission('purchases.manage'), 403);

        if ($record?->exists) {
            abort_if($record->status === PurchaseOrder::STATUS_CANCELLED, 422, 'Cancelled purchase orders cannot be edited.');
            $this->fillFromOrder($record->load('items'));
        } else {
            $this->order['ordered_on'] = today()->format('Y-m-d');
            $this->items = [$this->blankItem()];
        }
    }

    public function save(): mixed
    {
        abort_unless(auth()->user()?->hasPermission('purchases.manage'), 403);

        $validated = $this->validate();
        $manager = app(PurchaseOrderManager::class);

        if ($this->purchaseOrderId) {
            $order = $manager->updateOrder(PurchaseOrder::query()->findOrFail($this->purchaseOrderId), $validated, auth()->user());
            session()->flash('status', 'Purchase order updated.');
        } else {
            $order = $manager->createOrder($validated, auth()->user());
            session()->flash('status', 'Purchase order created.');
        }

        return $this->redirectRoute('purchase-orders.show', $order, navigate: false);
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
        if ($this->order['supplier_id'] === '') {
            return;
        }

        $supplier = Supplier::query()->findOrFail($this->order['supplier_id']);

        if ($supplier->payment_terms_days !== null && $this->order['payment_terms_days'] === '') {
            $this->order['payment_terms_days'] = (string) $supplier->payment_terms_days;
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
        return view('livewire.purchase-order-form', [
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'payment_terms_days']),
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
            'order.supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'order.order_number' => ['nullable', 'string', 'max:80', Rule::unique('purchase_orders', 'order_number')->ignore($this->purchaseOrderId)],
            'order.reference_number' => ['nullable', 'string', 'max:120'],
            'order.ordered_on' => ['required', 'date'],
            'order.expected_on' => ['nullable', 'date'],
            'order.payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'order.notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_name_snapshot' => ['required', 'string', 'max:180'],
            'items.*.unit_name' => ['required', 'string', 'max:80'],
            'items.*.quantity' => ['required', 'regex:/^(?!0+(?:\.0+)?$)\d{1,5}(?:\.\d{1,6})?$/'],
            'items.*.free_quantity' => ['nullable', 'regex:/^\d{1,5}(?:\.\d{1,6})?$/'],
            'items.*.unit_cost' => ['required', 'regex:/^(?!0+(?:\.0+)?$)\d{1,5}(?:\.\d{1,2})?$/'],
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
            'quantity' => '1',
            'free_quantity' => '0',
            'unit_cost' => '0.00',
            'discount_amount' => '0.00',
            'tax_rate_percent' => '0.00',
            'notes' => '',
        ];
    }

    private function fillFromOrder(PurchaseOrder $order): void
    {
        $this->purchaseOrderId = $order->id;
        $this->order = [
            'supplier_id' => $order->supplier_id ? (string) $order->supplier_id : '',
            'order_number' => $order->order_number,
            'reference_number' => $order->reference_number ?? '',
            'ordered_on' => $order->ordered_on?->format('Y-m-d') ?? today()->format('Y-m-d'),
            'expected_on' => $order->expected_on?->format('Y-m-d') ?? '',
            'payment_terms_days' => $order->payment_terms_days === null ? '' : (string) $order->payment_terms_days,
            'notes' => $order->notes ?? '',
        ];
        $this->items = $order->items->map(fn ($item): array => [
            'product_id' => $item->product_id ? (string) $item->product_id : '',
            'product_name_snapshot' => $item->product_name_snapshot,
            'unit_name' => $item->unit_name,
            'quantity' => $item->quantity,
            'free_quantity' => $item->free_quantity,
            'unit_cost' => $item->unit_cost,
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
            $lineSubtotal = max(0, (float) ($item['quantity'] ?? 0)) * max(0, (float) ($item['unit_cost'] ?? 0));
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
