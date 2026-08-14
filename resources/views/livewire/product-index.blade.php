<x-app-shell page-title="Products" section-label="Catalogue">
    <x-slot:actions>
        @if (session('status'))
            <span class="badge bg-medical-50 text-medical-700">{{ session('status') }}</span>
        @endif

        @if ($canManage)
            <a href="{{ route('catalogue.masters') }}" class="btn-secondary">
                Product Options
            </a>
            <a href="{{ route('products.create') }}" class="btn-primary">
                Add New Product
            </a>
        @endif
    </x-slot>

    <div class="space-y-5">
        <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <div class="metric-tile border-t-4 border-t-care-600">
                <p class="metric-label">Total</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-care-700">{{ $stats['total'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-medical-600">
                <p class="metric-label">Active</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-medical-700">{{ $stats['active'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-slate-400">
                <p class="metric-label">Deleted</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-slate-500">{{ $stats['inactive'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-alert-500">
                <p class="metric-label">Rx Required</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-alert-700">{{ $stats['rx'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-red-500">
                <p class="metric-label">Controlled</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-red-700">{{ $stats['controlled'] }}</p>
            </div>
        </section>

        <section class="surface-panel overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-slate-200/80 bg-white p-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="inline-flex w-fit flex-wrap gap-1 rounded-lg bg-slate-100 p-1">
                    @foreach (['active' => 'Active', 'all' => 'All Products', 'inactive' => 'Deleted'] as $value => $label)
                        <button
                            type="button"
                            wire:click="$set('statusFilter', '{{ $value }}')"
                            class="rounded-md px-3 py-2 text-sm font-semibold transition {{ $statusFilter === $value ? 'bg-white text-medical-800 shadow-sm' : 'text-slate-600 hover:bg-white/70 hover:text-ink-900' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="Search name, generic, SKU, barcode, HSN"
                    class="field-control xl:max-w-md"
                >
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="table-header">
                        <tr>
                            <th class="px-4 py-3">Product</th>
                            <th class="px-4 py-3">Class</th>
                            <th class="px-4 py-3">Unit</th>
                            <th class="px-4 py-3">Tax</th>
                            <th class="px-4 py-3">Flags</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($products as $item)
                            <tr class="transition hover:bg-care-50/60 {{ $item->is_active ? 'bg-white' : 'bg-slate-50/70' }}">
                                <td class="min-w-72 px-4 py-3 align-top">
                                    <p class="font-semibold text-ink-950">{{ $item->name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $item->generic_name ?: 'No generic name' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $item->sku ?: 'No SKU' }}
                                        @if ($item->barcodes->first())
                                            / {{ $item->barcodes->first()->barcode }}
                                        @endif
                                    </p>
                                </td>
                                <td class="min-w-56 px-4 py-3 align-top text-slate-700">
                                    <p>{{ $item->manufacturer?->name ?: 'No manufacturer' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $item->category?->name ?: 'No category' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $item->form ?: 'No form' }} {{ $item->strength ?: '' }}</p>
                                </td>
                                <td class="px-4 py-3 align-top text-slate-700">
                                    <p>{{ $item->baseUnit?->unit_name ?: 'Not set' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $item->baseUnit?->unit_code ?: '' }} {{ $item->baseUnit?->conversion_factor ?: '' }}</p>
                                </td>
                                <td class="px-4 py-3 align-top text-slate-700">
                                    <p>{{ $item->taxRate?->name ?: 'Not set' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        @if ($item->hsn_code)
                                            HSN {{ $item->hsn_code }}
                                        @endif
                                        @if ($item->taxRate?->rate_percent)
                                            {{ $item->taxRate->rate_percent }}%
                                        @endif
                                    </p>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="flex flex-wrap gap-2">
                                        @if ($item->prescription_required)
                                            <span class="badge bg-alert-50 text-alert-700">Rx</span>
                                        @endif
                                        @if ($item->controlled_medicine)
                                            <span class="badge bg-red-50 text-red-700">Controlled</span>
                                        @endif
                                        @if (! $item->prescription_required && ! $item->controlled_medicine)
                                            <span class="text-xs text-slate-500">None</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <span class="badge {{ $item->is_active ? 'bg-medical-50 text-medical-700' : 'bg-slate-200 text-slate-600' }}">
                                        {{ $item->is_active ? 'Active' : 'Deleted' }}
                                    </span>
                                </td>
                                <td class="min-w-40 px-4 py-3 text-right align-top">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('products.show', $item) }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50">
                                            View
                                        </a>
                                        @if ($canManage)
                                            <a href="{{ route('products.edit', $item) }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50">
                                                Edit
                                            </a>

                                            @if ($item->is_active)
                                                <button
                                                    type="button"
                                                    wire:click="deactivateProduct({{ $item->id }})"
                                                    onclick="return confirm('Delete this product from the active catalogue?')"
                                                    class="rounded-md border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-700 shadow-sm transition hover:bg-red-50"
                                                >
                                                    Delete
                                                </button>
                                            @else
                                                <button
                                                    type="button"
                                                    wire:click="restoreProduct({{ $item->id }})"
                                                    class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50"
                                                >
                                                    Restore
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-sm font-medium text-slate-500">No products found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-shell>
