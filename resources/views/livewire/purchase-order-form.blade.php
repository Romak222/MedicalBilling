<x-app-shell :page-title="$purchaseOrderId ? 'Edit Purchase Order' : 'Add Purchase Order'" section-label="Purchases">
    <x-slot:actions>
        <a href="{{ route('purchase-orders.index') }}" class="btn-secondary">Back to Orders</a>
        @if ($purchaseOrderId)
            <a href="{{ route('purchase-orders.show', $purchaseOrderId) }}" class="btn-secondary">View Order</a>
        @endif
        <button type="button" wire:click="save" class="btn-primary">{{ $purchaseOrderId ? 'Save Changes' : 'Create Order' }}</button>
    </x-slot>

    <form wire:submit="save" class="space-y-5">
        <section class="surface-panel p-5">
            <div class="flex flex-col gap-1 border-b border-slate-200 pb-4">
                <p class="section-kicker">Order Header</p>
                <h2 class="text-lg font-semibold text-ink-950">Supplier, Dates and Reference</h2>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-4">
                <label class="block lg:col-span-2">
                    <span class="text-sm font-medium text-ink-700">Supplier</span>
                    <select wire:model="order.supplier_id" wire:change="useSupplier" class="field-control mt-1">
                        <option value="">Choose supplier</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                    @error('order.supplier_id') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Order Number</span>
                    <input wire:model="order.order_number" type="text" class="field-control mt-1 uppercase" placeholder="Auto if blank">
                    @error('order.order_number') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Reference Number</span>
                    <input wire:model="order.reference_number" type="text" class="field-control mt-1">
                    @error('order.reference_number') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Order Date</span>
                    <input wire:model="order.ordered_on" type="date" class="field-control mt-1">
                    @error('order.ordered_on') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Expected Date</span>
                    <input wire:model="order.expected_on" type="date" class="field-control mt-1">
                    @error('order.expected_on') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Payment Terms Days</span>
                    <input wire:model="order.payment_terms_days" type="number" min="0" class="field-control mt-1">
                    @error('order.payment_terms_days') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block lg:col-span-4">
                    <span class="text-sm font-medium text-ink-700">Notes</span>
                    <textarea wire:model="order.notes" rows="3" class="field-control mt-1"></textarea>
                    @error('order.notes') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>
            </div>
        </section>

        <section class="surface-panel overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-slate-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="section-kicker">Order Lines</p>
                    <h2 class="mt-1 text-lg font-semibold text-ink-950">Products, Quantity and Cost</h2>
                </div>
                <button type="button" wire:click="addItem" class="btn-secondary">Add Line</button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="table-header">
                        <tr>
                            <th class="px-4 py-3">Product</th>
                            <th class="px-4 py-3">Unit</th>
                            <th class="px-4 py-3">Qty</th>
                            <th class="px-4 py-3">Free</th>
                            <th class="px-4 py-3">Cost</th>
                            <th class="px-4 py-3">Discount</th>
                            <th class="px-4 py-3">Tax %</th>
                            <th class="px-4 py-3">Notes</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($items as $index => $item)
                            <tr wire:key="purchase-order-item-{{ $index }}">
                                <td class="min-w-72 px-4 py-3 align-top">
                                    <select wire:model="items.{{ $index }}.product_id" wire:change="useProduct({{ $index }})" class="field-control">
                                        <option value="">Choose product</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}">{{ $product->name }}{{ $product->sku ? ' / '.$product->sku : '' }}</option>
                                        @endforeach
                                    </select>
                                    <input wire:model="items.{{ $index }}.product_name_snapshot" type="text" class="field-control mt-2" placeholder="Product snapshot">
                                    @error("items.$index.product_id") <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                                    @error("items.$index.product_name_snapshot") <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                                </td>
                                <td class="min-w-32 px-4 py-3 align-top">
                                    <input wire:model="items.{{ $index }}.unit_name" type="text" class="field-control">
                                    @error("items.$index.unit_name") <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                                </td>
                                <td class="min-w-28 px-4 py-3 align-top">
                                    <input wire:model.live="items.{{ $index }}.quantity" type="text" inputmode="decimal" class="field-control">
                                    @error("items.$index.quantity") <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                                </td>
                                <td class="min-w-28 px-4 py-3 align-top">
                                    <input wire:model.live="items.{{ $index }}.free_quantity" type="text" inputmode="decimal" class="field-control">
                                    @error("items.$index.free_quantity") <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                                </td>
                                <td class="min-w-32 px-4 py-3 align-top">
                                    <input wire:model.live="items.{{ $index }}.unit_cost" type="text" inputmode="decimal" class="field-control">
                                    @error("items.$index.unit_cost") <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                                </td>
                                <td class="min-w-32 px-4 py-3 align-top">
                                    <input wire:model.live="items.{{ $index }}.discount_amount" type="text" inputmode="decimal" class="field-control">
                                    @error("items.$index.discount_amount") <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                                </td>
                                <td class="min-w-28 px-4 py-3 align-top">
                                    <input wire:model.live="items.{{ $index }}.tax_rate_percent" type="text" inputmode="decimal" class="field-control">
                                    @error("items.$index.tax_rate_percent") <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                                </td>
                                <td class="min-w-52 px-4 py-3 align-top">
                                    <input wire:model="items.{{ $index }}.notes" type="text" class="field-control">
                                    @error("items.$index.notes") <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                                </td>
                                <td class="px-4 py-3 text-right align-top">
                                    <button type="button" wire:click="removeItem({{ $index }})" class="rounded-md border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-700 shadow-sm transition hover:bg-red-50">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="surface-panel p-5">
            <div class="grid gap-3 md:grid-cols-4">
                <div class="rounded-lg border border-slate-200 bg-slate-50/80 p-4">
                    <p class="metric-label">Subtotal</p>
                    <p class="mt-2 text-xl font-semibold text-ink-950">{{ number_format($previewTotals['subtotal'], 2) }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50/80 p-4">
                    <p class="metric-label">Discount</p>
                    <p class="mt-2 text-xl font-semibold text-ink-950">{{ number_format($previewTotals['discount'], 2) }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50/80 p-4">
                    <p class="metric-label">Tax</p>
                    <p class="mt-2 text-xl font-semibold text-ink-950">{{ number_format($previewTotals['tax'], 2) }}</p>
                </div>
                <div class="rounded-lg border border-medical-100 bg-medical-50 p-4">
                    <p class="metric-label text-medical-700">Total</p>
                    <p class="mt-2 text-xl font-semibold text-medical-800">{{ number_format($previewTotals['total'], 2) }}</p>
                </div>
            </div>
        </section>

        <div class="sticky bottom-0 flex items-center justify-end gap-3 rounded-lg border border-white/80 bg-white/95 p-4 shadow-lg shadow-slate-900/10 backdrop-blur">
            <a href="{{ route('purchase-orders.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $purchaseOrderId ? 'Save Changes' : 'Create Order' }}</button>
        </div>
    </form>
</x-app-shell>
