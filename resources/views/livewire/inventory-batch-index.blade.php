<x-app-shell page-title="Inventory Batches" section-label="Inventory">
    <x-slot:actions>
        <a href="{{ route('purchase-invoices.index') }}" class="btn-primary">
            Receive Stock
        </a>
        @if (auth()->user()?->hasPermission('inventory.adjust'))
            <a href="{{ route('inventory.adjustments.index') }}" class="btn-secondary">Stock Adjustments</a>
        @endif
    </x-slot>

    <div class="space-y-5">
        <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <div class="metric-tile border-t-4 border-t-care-600">
                <p class="metric-label">Batches</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-care-700">{{ $stats['batches'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-medical-600">
                <p class="metric-label">Available</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-medical-700">{{ $stats['available'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-alert-500">
                <p class="metric-label">Expiring 90d</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-alert-700">{{ $stats['expiring'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-red-500">
                <p class="metric-label">Expired</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-red-700">{{ $stats['expired'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-slate-400">
                <p class="metric-label">Movements</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-slate-500">{{ $stats['movements'] }}</p>
            </div>
        </section>

        <section class="surface-panel overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-slate-200/80 bg-white p-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="inline-flex w-fit flex-wrap gap-1 rounded-lg bg-slate-100 p-1">
                    @foreach (['available' => 'Available', 'expiring' => 'Expiring 90d', 'expired' => 'Expired', 'all' => 'All Batches'] as $value => $label)
                        <button
                            type="button"
                            wire:click="$set('expiryFilter', '{{ $value }}')"
                            class="rounded-md px-3 py-2 text-sm font-semibold transition {{ $expiryFilter === $value ? 'bg-white text-medical-800 shadow-sm' : 'text-slate-600 hover:bg-white/70 hover:text-ink-900' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="Search product, SKU, generic, batch"
                    class="field-control xl:max-w-md"
                >
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="table-header">
                        <tr>
                            <th class="px-4 py-3">Product</th>
                            <th class="px-4 py-3">Batch</th>
                            <th class="px-4 py-3">Expiry</th>
                            <th class="px-4 py-3">Available</th>
                            <th class="px-4 py-3">MRP</th>
                            <th class="px-4 py-3">Rates</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($batches as $batch)
                            <tr class="transition hover:bg-care-50/60">
                                <td class="min-w-72 px-4 py-3 align-top">
                                    <p class="font-semibold text-ink-950">{{ $batch->product?->name ?: 'Deleted product' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $batch->product?->generic_name ?: 'No generic name' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $batch->product?->sku ?: 'No SKU' }}</p>
                                </td>
                                <td class="px-4 py-3 align-top text-slate-700">
                                    <p class="font-semibold text-ink-900">{{ $batch->batch_number }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        @if ($batch->manufactured_on)
                                            MFG {{ $batch->manufactured_on->format('d M Y') }}
                                        @else
                                            MFG not set
                                        @endif
                                    </p>
                                </td>
                                <td class="px-4 py-3 align-top text-slate-700">
                                    <p>{{ $batch->expires_on->format('d M Y') }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $batch->expires_on->diffForHumans(null, true) }}</p>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <p class="font-semibold text-ink-900">{{ $batch->available_quantity }}</p>
                                </td>
                                <td class="px-4 py-3 align-top text-slate-700">{{ number_format((float) $batch->mrp, 2) }}</td>
                                <td class="px-4 py-3 align-top text-slate-700">
                                    <p>Purchase {{ number_format((float) $batch->purchase_rate, 2) }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Sale {{ $batch->sale_rate === null ? 'not set' : number_format((float) $batch->sale_rate, 2) }}</p>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    @if ($batch->is_blocked)
                                        <span class="badge bg-slate-200 text-slate-600">Blocked</span>
                                    @elseif ($batch->expires_on->lte(today()))
                                        <span class="badge bg-red-50 text-red-700">Expired</span>
                                    @elseif ($batch->expires_on->lte(today()->addDays(90)))
                                        <span class="badge bg-alert-50 text-alert-700">Expiring</span>
                                    @else
                                        <span class="badge bg-medical-50 text-medical-700">Good</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-sm font-medium text-slate-500">No batches found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-shell>
