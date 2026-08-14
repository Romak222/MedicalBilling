<x-app-shell :page-title="'Return '.$purchaseInvoice->invoice_number" section-label="Purchase Return">
    <x-slot:actions>
        <a href="{{ route('purchase-invoices.show', $purchaseInvoice) }}" class="btn-secondary">Back to Invoice</a>
        <button type="button" wire:click="save" class="btn-primary">Finalize Return</button>
    </x-slot>

    <form wire:submit="save" class="space-y-5">
        <section class="surface-panel p-5">
            <div class="border-b border-slate-200 pb-4"><p class="section-kicker">Source Document</p><h2 class="mt-1 text-lg font-semibold text-ink-950">{{ $purchaseInvoice->invoice_number }}</h2><p class="mt-2 text-sm text-slate-600">{{ $purchaseInvoice->supplier?->name }} / received {{ $purchaseInvoice->received_on?->format('d M Y') }}</p></div>
            <div class="mt-5 grid gap-4 lg:grid-cols-3">
                <label class="block"><span class="field-label">Return Number</span><input wire:model="return.return_number" type="text" class="field-control mt-1 uppercase" placeholder="Auto if blank">@error('return.return_number') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror</label>
                <label class="block"><span class="field-label">Return Date</span><input wire:model="return.return_date" type="date" class="field-control mt-1">@error('return.return_date') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror</label>
                <label class="block"><span class="field-label">Reason</span><input wire:model="return.reason" type="text" class="field-control mt-1" placeholder="Damaged, expired, excess, or recall">@error('return.reason') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror</label>
                <label class="block lg:col-span-3"><span class="field-label">Notes</span><textarea wire:model="return.notes" rows="3" class="field-control mt-1" placeholder="Supplier credit note or inspection details"></textarea>@error('return.notes') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror</label>
            </div>
        </section>

        <section class="surface-panel overflow-hidden">
            <div class="border-b border-slate-200 bg-white p-5"><p class="section-kicker">Return Lines</p><h2 class="mt-1 text-lg font-semibold text-ink-950">Select stock to return</h2><p class="mt-2 text-sm text-slate-600">Only currently available stock can be returned. Finalizing creates negative stock movements and a supplier credit.</p></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="table-header"><tr><th class="px-4 py-3">Product / Batch</th><th class="px-4 py-3">Received</th><th class="px-4 py-3">Returned</th><th class="px-4 py-3">Available Stock</th><th class="px-4 py-3">Return Qty</th><th class="px-4 py-3">Free Qty</th><th class="px-4 py-3 text-right">Action</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($purchaseInvoice->items as $index => $item)
                            @php
                                $returnedQty = $item->purchaseReturnItems->sum(fn ($returnItem) => (float) $returnItem->quantity);
                                $returnedFree = $item->purchaseReturnItems->sum(fn ($returnItem) => (float) $returnItem->free_quantity);
                            @endphp
                            <tr wire:key="purchase-return-item-{{ $item->id }}">
                                <td class="min-w-64 px-4 py-4 align-top"><p class="font-semibold text-ink-950">{{ $item->product_name_snapshot }}</p><p class="mt-1 text-xs text-slate-500">Batch {{ $item->batch_number }} / {{ $item->expires_on?->format('d M Y') }}</p></td>
                                <td class="px-4 py-4 align-top text-slate-700"><p>{{ $item->quantity }}</p><p class="mt-1 text-xs text-slate-500">Free {{ $item->free_quantity }}</p></td>
                                <td class="px-4 py-4 align-top text-slate-700"><p>{{ number_format($returnedQty, 6) }}</p><p class="mt-1 text-xs text-slate-500">Free {{ number_format($returnedFree, 6) }}</p></td>
                                <td class="px-4 py-4 align-top text-slate-700">{{ $item->productBatch?->available_quantity ?: '0.000000' }}</td>
                                <td class="min-w-32 px-4 py-4 align-top"><input wire:model="items.{{ $index }}.quantity" type="text" inputmode="decimal" class="field-control">@error("items.$index.quantity") <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror</td>
                                <td class="min-w-32 px-4 py-4 align-top"><input wire:model="items.{{ $index }}.free_quantity" type="text" inputmode="decimal" class="field-control">@error("items.$index.free_quantity") <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror</td>
                                <td class="px-4 py-4 text-right align-top"><button type="button" wire:click="useRemaining({{ $index }})" class="btn-secondary">Use Remaining</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="grid gap-3 md:grid-cols-3">
            <div class="metric-tile"><p class="metric-label">Preview Subtotal</p><p class="mt-2 text-xl font-semibold text-ink-950">{{ $previewTotals['subtotal'] }}</p></div>
            <div class="metric-tile"><p class="metric-label">Preview Tax</p><p class="mt-2 text-xl font-semibold text-ink-950">{{ $previewTotals['tax'] }}</p></div>
            <div class="metric-tile border-t-4 border-t-alert-500"><p class="metric-label">Preview Supplier Credit</p><p class="mt-2 text-xl font-semibold text-alert-700">{{ $previewTotals['total'] }}</p></div>
        </section>
        <div class="flex justify-end gap-3"><a href="{{ route('purchase-invoices.show', $purchaseInvoice) }}" class="btn-secondary">Cancel</a><button type="submit" class="btn-primary">Finalize Return</button></div>
    </form>
</x-app-shell>
