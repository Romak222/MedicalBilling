<x-app-shell page-title="Stock Adjustment" section-label="Inventory Control">
    <x-slot:actions>
        <a href="{{ route('inventory.adjustments.index') }}" class="btn-secondary">Back to Adjustments</a>
        <button type="submit" form="stock-adjustment-form" class="btn-primary">Finalize Adjustment</button>
    </x-slot>

    <form id="stock-adjustment-form" wire:submit="save" class="space-y-5">
        <section class="surface-panel p-5">
            <div class="border-b border-slate-200 pb-4">
                <p class="section-kicker">Controlled inventory correction</p>
                <h2 class="mt-1 text-lg font-semibold text-ink-950">Reconcile physical count to the stock ledger</h2>
                <p class="mt-2 text-sm text-slate-600">Every changed batch creates an immutable signed stock movement and an audit-linked adjustment document.</p>
            </div>
            <div class="mt-5 grid gap-4 md:grid-cols-3">
                <label class="block"><span class="field-label">Adjustment Number</span><input wire:model="adjustment.adjustment_number" type="text" class="field-control mt-1 uppercase" placeholder="Auto if blank">@error('adjustment.adjustment_number') <span class="field-error">{{ $message }}</span> @enderror</label>
                <label class="block"><span class="field-label">Adjustment Date</span><input wire:model="adjustment.adjustment_date" type="date" class="field-control mt-1">@error('adjustment.adjustment_date') <span class="field-error">{{ $message }}</span> @enderror</label>
                <label class="block"><span class="field-label">Reason</span><input wire:model="adjustment.reason" type="text" class="field-control mt-1" placeholder="Cycle count, damage, expiry, theft, correction">@error('adjustment.reason') <span class="field-error">{{ $message }}</span> @enderror</label>
                <label class="block md:col-span-3"><span class="field-label">Notes</span><textarea wire:model="adjustment.notes" rows="3" class="field-control mt-1" placeholder="Count sheet, approval, or inspection reference"></textarea>@error('adjustment.notes') <span class="field-error">{{ $message }}</span> @enderror</label>
            </div>
        </section>

        <section class="surface-panel overflow-hidden">
            <div class="border-b border-slate-200 bg-white p-5">
                <p class="section-kicker">Batch count lines</p>
                <h2 class="mt-1 text-lg font-semibold text-ink-950">Add a counted quantity</h2>
                <div class="mt-4 grid gap-3 lg:grid-cols-[1fr_180px_auto]">
                    <select wire:model="newBatchId" class="field-control"><option value="">Select product batch</option>@foreach ($batches as $batch)<option value="{{ $batch->id }}">{{ $batch->product?->name }} / {{ $batch->batch_number }} / ledger {{ $batch->available_quantity }}</option>@endforeach</select>
                    <input wire:model="newCountedQuantity" type="text" inputmode="decimal" class="field-control" placeholder="Physical count">
                    <button type="button" wire:click="addLine" class="btn-secondary">Add Count</button>
                </div>
                @error('newBatchId') <span class="field-error">{{ $message }}</span> @enderror
                @error('newCountedQuantity') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="table-header"><tr><th class="px-4 py-3">Batch</th><th class="px-4 py-3">Ledger Quantity</th><th class="px-4 py-3">Counted Quantity</th><th class="px-4 py-3">Difference</th><th class="px-4 py-3 text-right">Action</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($items as $index => $item)
                            @php $batch = $batches->firstWhere('id', (int) $item['product_batch_id']); @endphp
                            <tr wire:key="stock-adjustment-line-{{ $index }}">
                                <td class="px-4 py-4 align-top"><p class="font-semibold text-ink-950">{{ $batch?->product?->name ?: 'Select a batch' }}</p><p class="mt-1 text-xs text-slate-500">{{ $batch?->batch_number ?: 'No batch selected' }}</p></td>
                                <td class="px-4 py-4 align-top text-slate-700">{{ $batch?->available_quantity ?: '0.000000' }}</td>
                                <td class="min-w-44 px-4 py-4 align-top"><input wire:model="items.{{ $index }}.counted_quantity" type="text" inputmode="decimal" class="field-control">@error("items.$index.counted_quantity") <span class="field-error">{{ $message }}</span> @enderror</td>
                                <td class="px-4 py-4 align-top font-semibold text-ink-900">Enter count to calculate</td>
                                <td class="px-4 py-4 text-right align-top"><button type="button" wire:click="removeLine({{ $index }})" class="btn-secondary">Remove</button></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-12 text-center text-sm font-medium text-slate-500">Add at least one changed batch.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <div class="flex justify-end gap-3"><a href="{{ route('inventory.batches.index') }}" class="btn-secondary">Cancel</a><button type="submit" class="btn-primary">Finalize Adjustment</button></div>
    </form>
</x-app-shell>
