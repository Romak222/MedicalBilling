<x-app-shell page-title="Receive Stock" section-label="Purchases">
    <x-slot:actions>
        @if (session('status'))
            <span class="badge bg-medical-50 text-medical-700">{{ session('status') }}</span>
        @endif

        <a href="{{ route('purchase-orders.index') }}" class="btn-secondary">
            Purchase Orders
        </a>
        @if ($canManagePurchases)
            <a href="{{ route('purchase-invoices.create') }}" class="btn-primary">
                Add Purchase Invoice
            </a>
        @endif
    </x-slot>

    <div class="space-y-5">
        <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <div class="metric-tile border-t-4 border-t-care-600">
                <p class="metric-label">Total</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-care-700">{{ $stats['total'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-alert-500">
                <p class="metric-label">Draft</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-alert-700">{{ $stats['draft'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-medical-600">
                <p class="metric-label">Finalized</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-medical-700">{{ $stats['finalized'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-slate-400">
                <p class="metric-label">Cancelled</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-slate-500">{{ $stats['cancelled'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-red-500">
                <p class="metric-label">Received Value</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-red-700">{{ number_format((float) $stats['received_value'], 2) }}</p>
            </div>
        </section>

        <section class="surface-panel overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-slate-200/80 bg-white p-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="inline-flex w-fit flex-wrap gap-1 rounded-lg bg-slate-100 p-1">
                    @foreach (['open' => 'Open Drafts', 'draft' => 'Draft', 'finalized' => 'Finalized', 'cancelled' => 'Cancelled', 'all' => 'All Invoices'] as $value => $label)
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
                    placeholder="Search invoice, supplier, purchase order"
                    class="field-control xl:max-w-md"
                >
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="table-header">
                        <tr>
                            <th class="px-4 py-3">Invoice</th>
                            <th class="px-4 py-3">Supplier</th>
                            <th class="px-4 py-3">Dates</th>
                            <th class="px-4 py-3">Lines</th>
                            <th class="px-4 py-3">Totals</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($invoices as $item)
                            <tr class="transition hover:bg-care-50/60 {{ $item->status === 'cancelled' ? 'bg-slate-50/70' : 'bg-white' }}">
                                <td class="min-w-56 px-4 py-3 align-top">
                                    <p class="font-semibold text-ink-950">{{ $item->invoice_number }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $item->purchaseOrder?->order_number ?: 'No purchase order' }}</p>
                                </td>
                                <td class="min-w-60 px-4 py-3 align-top text-slate-700">
                                    <p>{{ $item->supplier?->name ?: $item->supplier_name_snapshot }}</p>
                                </td>
                                <td class="px-4 py-3 align-top text-slate-700">
                                    <p>Invoice {{ $item->invoice_date?->format('d M Y') }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Received {{ $item->received_on?->format('d M Y') }}</p>
                                </td>
                                <td class="px-4 py-3 align-top text-slate-700">
                                    <p>{{ $item->items_count }} items</p>
                                    <p class="mt-1 text-xs text-slate-500">Finalizing creates stock</p>
                                </td>
                                <td class="px-4 py-3 align-top text-slate-700">
                                    <p class="font-semibold text-ink-900">{{ number_format((float) $item->total_amount, 2) }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Tax {{ number_format((float) $item->tax_amount, 2) }}</p>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <span class="badge {{ $item->status === 'draft' ? 'bg-alert-50 text-alert-700' : ($item->status === 'finalized' ? 'bg-medical-50 text-medical-700' : 'bg-slate-200 text-slate-600') }}">
                                        {{ ucfirst($item->status) }}
                                    </span>
                                </td>
                                <td class="min-w-56 px-4 py-3 text-right align-top">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('purchase-invoices.show', $item) }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50">
                                            View
                                        </a>

                                        @if ($canManagePurchases && $item->status === 'draft')
                                            <a href="{{ route('purchase-invoices.edit', $item) }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50">
                                                Edit
                                            </a>
                                            <button
                                                type="button"
                                                wire:click="cancelDraft({{ $item->id }})"
                                                onclick="return confirm('Cancel this draft purchase invoice?')"
                                                class="rounded-md border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-700 shadow-sm transition hover:bg-red-50"
                                            >
                                                Cancel
                                            </button>
                                        @endif

                                        @if ($canManageInventory && $item->status === 'draft')
                                            <button
                                                type="button"
                                                wire:click="finalizeInvoice({{ $item->id }})"
                                                onclick="return confirm('Finalize this invoice and receive stock into batches?')"
                                                class="rounded-md border border-medical-200 bg-white px-3 py-2 text-xs font-semibold text-medical-700 shadow-sm transition hover:bg-medical-50"
                                            >
                                                Finalize
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-sm font-medium text-slate-500">No purchase invoices found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-shell>
