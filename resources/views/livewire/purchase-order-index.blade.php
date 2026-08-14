<x-app-shell page-title="Purchase Orders" section-label="Purchases">
    <x-slot:actions>
        @if (session('status'))
            <span class="badge bg-medical-50 text-medical-700">{{ session('status') }}</span>
        @endif

        @if ($canManage)
            <a href="{{ route('purchase-orders.create') }}" class="btn-primary">
                Add Purchase Order
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
                <p class="metric-label">Draft</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-medical-700">{{ $stats['draft'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-alert-500">
                <p class="metric-label">Sent</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-alert-700">{{ $stats['sent'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-slate-400">
                <p class="metric-label">Cancelled</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-slate-500">{{ $stats['cancelled'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-red-500">
                <p class="metric-label">Open Value</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-red-700">{{ number_format((float) $stats['open_value'], 2) }}</p>
            </div>
        </section>

        <section class="surface-panel overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-slate-200/80 bg-white p-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="inline-flex w-fit flex-wrap gap-1 rounded-lg bg-slate-100 p-1">
                    @foreach (['open' => 'Open', 'draft' => 'Draft', 'sent' => 'Sent', 'cancelled' => 'Cancelled', 'all' => 'All Orders'] as $value => $label)
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
                    placeholder="Search order number, reference, supplier"
                    class="field-control xl:max-w-md"
                >
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="table-header">
                        <tr>
                            <th class="px-4 py-3">Order</th>
                            <th class="px-4 py-3">Supplier</th>
                            <th class="px-4 py-3">Dates</th>
                            <th class="px-4 py-3">Lines</th>
                            <th class="px-4 py-3">Totals</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($orders as $item)
                            <tr class="transition hover:bg-care-50/60 {{ $item->status === 'cancelled' ? 'bg-slate-50/70' : 'bg-white' }}">
                                <td class="min-w-56 px-4 py-3 align-top">
                                    <p class="font-semibold text-ink-950">{{ $item->order_number }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $item->reference_number ?: 'No reference' }}</p>
                                </td>
                                <td class="min-w-60 px-4 py-3 align-top text-slate-700">
                                    <p>{{ $item->supplier?->name ?: $item->supplier_name_snapshot }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $item->payment_terms_days === null ? 'No terms' : $item->payment_terms_days.' days terms' }}</p>
                                </td>
                                <td class="px-4 py-3 align-top text-slate-700">
                                    <p>{{ $item->ordered_on?->format('d M Y') }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        @if ($item->expected_on)
                                            Expected {{ $item->expected_on->format('d M Y') }}
                                        @else
                                            No expected date
                                        @endif
                                    </p>
                                </td>
                                <td class="px-4 py-3 align-top text-slate-700">
                                    <p>{{ $item->items_count }} items</p>
                                    <p class="mt-1 text-xs text-slate-500">Draft-only stock impact</p>
                                </td>
                                <td class="px-4 py-3 align-top text-slate-700">
                                    <p class="font-semibold text-ink-900">{{ number_format((float) $item->total_amount, 2) }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Tax {{ number_format((float) $item->tax_amount, 2) }}</p>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <span class="badge {{ $item->status === 'draft' ? 'bg-care-50 text-care-700' : ($item->status === 'sent' ? 'bg-alert-50 text-alert-700' : 'bg-slate-200 text-slate-600') }}">
                                        {{ ucfirst($item->status) }}
                                    </span>
                                </td>
                                <td class="min-w-56 px-4 py-3 text-right align-top">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('purchase-orders.show', $item) }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50">
                                            View
                                        </a>

                                        @if ($canManage)
                                            @if ($item->status !== 'cancelled')
                                                <a href="{{ route('purchase-orders.edit', $item) }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50">
                                                    Edit
                                                </a>
                                            @endif

                                            @if ($item->status === 'draft')
                                                <button type="button" wire:click="markSent({{ $item->id }})" class="rounded-md border border-alert-200 bg-white px-3 py-2 text-xs font-semibold text-alert-700 shadow-sm transition hover:bg-alert-50">
                                                    Send
                                                </button>
                                            @endif

                                            @if ($item->status !== 'cancelled')
                                                <button
                                                    type="button"
                                                    wire:click="cancelOrder({{ $item->id }})"
                                                    onclick="return confirm('Cancel this purchase order?')"
                                                    class="rounded-md border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-700 shadow-sm transition hover:bg-red-50"
                                                >
                                                    Cancel
                                                </button>
                                            @else
                                                <button type="button" wire:click="reopenOrder({{ $item->id }})" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50">
                                                    Reopen
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-sm font-medium text-slate-500">No purchase orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-shell>
