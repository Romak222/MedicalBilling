<x-app-shell page-title="Billing" section-label="POS">
    <x-slot:actions>
        @if (session('status'))
            <span class="badge bg-medical-50 text-medical-700">{{ session('status') }}</span>
        @endif

        @if ($canManage)
            <a href="{{ route('sales-invoices.create') }}" class="btn-primary">
                New Bill
            </a>
        @endif
    </x-slot>

    <div class="space-y-5">
        <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <div class="metric-tile border-t-4 border-t-care-600">
                <p class="metric-label">Total Bills</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-care-700">{{ $stats['total'] }}</p>
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
                <p class="metric-label">Sales Value</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-red-700">{{ number_format((float) $stats['sales_value'], 2) }}</p>
            </div>
        </section>

        <section class="surface-panel overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-slate-200/80 bg-white p-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="inline-flex w-fit flex-wrap gap-1 rounded-lg bg-slate-100 p-1">
                    @foreach (['finalized' => 'Finalized', 'cancelled' => 'Cancelled', 'all' => 'All Bills'] as $value => $label)
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
                    placeholder="Search invoice, prescription, doctor, patient, phone"
                    class="field-control xl:max-w-md"
                >
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="table-header">
                        <tr>
                            <th class="px-4 py-3">Bill</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Items</th>
                            <th class="px-4 py-3">Payment</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($invoices as $item)
                            <tr class="transition hover:bg-care-50/60 {{ $item->status === 'cancelled' ? 'bg-slate-50/70' : 'bg-white' }}">
                                <td class="min-w-56 px-4 py-3 align-top">
                                    <p class="font-semibold text-ink-950">{{ $item->invoice_number }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $item->invoice_date?->format('d M Y') }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $item->prescription_number ?: 'No prescription' }}</p>
                                </td>
                                <td class="min-w-52 px-4 py-3 align-top text-slate-700">
                                    <p>{{ $item->customer_name ?: ($item->patient_name ?: 'Walk-in customer') }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $item->customer_phone ?: ($item->patient_phone ?: 'No phone') }}</p>
                                    @if ($item->patient_name)
                                        <p class="mt-1 text-xs text-slate-500">Patient {{ $item->patient_name }}</p>
                                    @endif
                                    @if ($item->doctor_name)
                                        <p class="mt-1 text-xs text-slate-500">Doctor {{ $item->doctor_name }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-top text-slate-700">{{ $item->items_count }} items</td>
                                <td class="px-4 py-3 align-top text-slate-700">
                                    <p>{{ ucfirst($item->payment_method ?: 'Not set') }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Paid {{ number_format((float) $item->paid_amount, 2) }}</p>
                                </td>
                                <td class="px-4 py-3 align-top font-semibold text-ink-900">{{ number_format((float) $item->total_amount, 2) }}</td>
                                <td class="px-4 py-3 align-top">
                                    <span class="badge {{ $item->status === 'finalized' ? 'bg-medical-50 text-medical-700' : 'bg-slate-200 text-slate-600' }}">
                                        {{ ucfirst($item->status) }}
                                    </span>
                                </td>
                                <td class="min-w-40 px-4 py-3 text-right align-top">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('sales-invoices.show', $item) }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50">
                                            View
                                        </a>

                                        @if ($canManage && $item->status === 'finalized' && $item->sales_returns_count === 0)
                                            <button
                                                type="button"
                                                wire:click="cancelInvoice({{ $item->id }})"
                                                onclick="return confirm('Cancel this sales invoice and reverse stock?')"
                                                class="rounded-md border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-700 shadow-sm transition hover:bg-red-50"
                                            >
                                                Cancel
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-sm font-medium text-slate-500">No bills found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-shell>
