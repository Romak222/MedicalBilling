<x-app-shell :page-title="'Return for '.$salesInvoice->invoice_number" section-label="Sales Return">
    <x-slot:actions>
        <a href="{{ route('sales-invoices.show', $salesInvoice) }}" class="btn-secondary">Back to Bill</a>
        <button type="button" wire:click="save" class="btn-primary">Finalize Return</button>
    </x-slot>

    <form wire:submit="save" class="space-y-5">
        @if (session('status'))
            <div class="surface-panel border-medical-100 bg-medical-50 px-4 py-3 text-sm font-semibold text-medical-800">
                {{ session('status') }}
            </div>
        @endif

        <section class="surface-panel p-5">
            <div class="flex flex-col gap-1 border-b border-slate-200 pb-4">
                <p class="section-kicker">Original Bill</p>
                <h2 class="text-lg font-semibold text-ink-950">{{ $salesInvoice->invoice_number }}</h2>
                <p class="text-sm text-slate-600">{{ $salesInvoice->customer_name ?: ($salesInvoice->patient_name ?: 'Walk-in customer') }} / {{ $salesInvoice->invoice_date?->format('d M Y') }}</p>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-4">
                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Return Number</span>
                    <input wire:model="return.return_number" type="text" class="field-control mt-1 uppercase" placeholder="Auto if blank">
                    @error('return.return_number') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Return Date</span>
                    <input wire:model="return.return_date" type="date" class="field-control mt-1">
                    @error('return.return_date') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Refund Method</span>
                    <select wire:model="return.refund_method" class="field-control mt-1">
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="upi">UPI</option>
                        <option value="store_credit">Store Credit</option>
                    </select>
                    @error('return.refund_method') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Refund Amount</span>
                    <input wire:model="return.refund_amount" type="text" inputmode="decimal" class="field-control mt-1">
                    @error('return.refund_amount') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block lg:col-span-4">
                    <span class="text-sm font-medium text-ink-700">Notes</span>
                    <input wire:model="return.notes" type="text" class="field-control mt-1" placeholder="Reason, pack condition, pharmacist note">
                    @error('return.notes') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>
            </div>
        </section>

        <section class="surface-panel overflow-hidden">
            <div class="border-b border-slate-200 bg-white p-5">
                <p class="section-kicker">Return Items</p>
                <h2 class="mt-1 text-lg font-semibold text-ink-950">Returned Quantities</h2>
                <p class="mt-2 text-sm text-slate-500">Restock only after physical inspection confirms the pack is sealed, saleable, and within expiry.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="table-header">
                        <tr>
                            <th class="px-4 py-3">Product</th>
                            <th class="px-4 py-3">Batch</th>
                            <th class="px-4 py-3">Sold</th>
                            <th class="px-4 py-3">Already Returned</th>
                            <th class="px-4 py-3">Remaining</th>
                            <th class="px-4 py-3">Return Qty</th>
                            <th class="px-4 py-3">Restock</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($salesInvoice->items as $index => $invoiceItem)
                            @php
                                $returnedQuantity = $invoiceItem->salesReturnItems->sum(fn ($returnItem) => (float) $returnItem->quantity);
                                $remainingQuantity = max(0, (float) $invoiceItem->quantity - $returnedQuantity);
                                $batchExpired = ! $invoiceItem->productBatch || ! $invoiceItem->productBatch->expires_on || $invoiceItem->productBatch->expires_on->lte(today());
                                $batchBlocked = $invoiceItem->productBatch?->is_blocked ?? false;
                                $zeroQuantity = ((float) ($items[$index]['quantity'] ?? 0)) <= 0;
                            @endphp
                            <tr wire:key="sales-return-item-{{ $invoiceItem->id }}">
                                <td class="min-w-64 px-4 py-3 align-top">
                                    <p class="font-semibold text-ink-950">{{ $invoiceItem->product_name_snapshot }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $invoiceItem->unit_name }}</p>
                                </td>
                                <td class="px-4 py-3 align-top text-slate-700">
                                    <p>{{ $invoiceItem->batch_number_snapshot }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $invoiceItem->expires_on_snapshot?->format('d M Y') ?: 'No expiry' }}</p>
                                </td>
                                <td class="px-4 py-3 align-top text-slate-700">{{ $invoiceItem->quantity }}</td>
                                <td class="px-4 py-3 align-top text-slate-700">{{ number_format($returnedQuantity, 6, '.', '') }}</td>
                                <td class="px-4 py-3 align-top font-semibold text-ink-900">{{ number_format($remainingQuantity, 6, '.', '') }}</td>
                                <td class="min-w-32 px-4 py-3 align-top">
                                    <input wire:model.live="items.{{ $index }}.quantity" type="text" inputmode="decimal" class="field-control">
                                    <input type="hidden" wire:model="items.{{ $index }}.sales_invoice_item_id">
                                    @error("items.$index.quantity") <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                                </td>
                                <td class="min-w-40 px-4 py-3 align-top">
                                    <label class="inline-flex items-center gap-2 text-sm text-ink-700">
                                        <input
                                            wire:model="items.{{ $index }}.restock_to_inventory"
                                            type="checkbox"
                                            class="h-4 w-4 rounded border-slate-300 text-medical-700 focus:ring-medical-600"
                                            @disabled($remainingQuantity <= 0 || $batchExpired || $batchBlocked || $zeroQuantity)
                                        >
                                        <span>{{ $batchExpired ? 'Expired' : ($batchBlocked ? 'Blocked' : 'Return to stock') }}</span>
                                    </label>
                                    @error("items.$index.restock_to_inventory") <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                                </td>
                                <td class="px-4 py-3 text-right align-top">
                                    <button type="button" wire:click="useRemaining({{ $index }})" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50">
                                        Full Remaining
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
                    <p class="metric-label text-medical-700">Refund Total</p>
                    <p class="mt-2 text-xl font-semibold text-medical-800">{{ number_format($previewTotals['total'], 2) }}</p>
                </div>
            </div>
        </section>

        <div class="sticky bottom-0 flex items-center justify-end gap-3 rounded-lg border border-white/80 bg-white/95 p-4 shadow-lg shadow-slate-900/10 backdrop-blur">
            <a href="{{ route('sales-invoices.show', $salesInvoice) }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Finalize Return</button>
        </div>
    </form>
</x-app-shell>
