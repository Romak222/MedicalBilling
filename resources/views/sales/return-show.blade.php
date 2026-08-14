<x-layouts.app :title="config('app.name').' Sales Return Detail'">
    <x-app-shell :page-title="$salesReturn->return_number" section-label="Sales Return Detail">
        <x-slot:actions>
            <a href="{{ route('sales-invoices.show', $salesReturn->salesInvoice) }}" class="btn-secondary">Back to Bill</a>
        </x-slot>

        <div class="space-y-5">
            @if (session('status'))
                <div class="surface-panel border-medical-100 bg-medical-50 px-4 py-3 text-sm font-semibold text-medical-800">
                    {{ session('status') }}
                </div>
            @endif

            <section class="surface-panel p-5">
                <div class="flex flex-col gap-3 border-b border-slate-200 pb-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="section-kicker">Read Only</p>
                        <h2 class="mt-1 text-xl font-semibold text-ink-950">{{ $salesReturn->return_number }}</h2>
                        <p class="mt-2 text-sm text-slate-600">Against {{ $salesReturn->salesInvoice->invoice_number }}</p>
                    </div>
                    <span class="badge bg-medical-50 text-medical-700">{{ ucfirst($salesReturn->status) }}</span>
                </div>

                <dl class="mt-5 grid gap-4 lg:grid-cols-4">
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Return Date</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $salesReturn->return_date?->format('d M Y') }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Refund Method</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ ucfirst(str_replace('_', ' ', $salesReturn->refund_method ?: 'not set')) }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Cash Drawer Shift</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">
                            @if ($salesReturn->cashDrawerShift)
                                <a href="{{ route('cash-drawer.show', $salesReturn->cashDrawerShift) }}" class="text-medical-700 underline-offset-2 hover:underline">{{ $salesReturn->cashDrawerShift->shift_number }}</a>
                            @elseif ($salesReturn->refund_method === 'cash')
                                Unassigned
                            @else
                                Not applicable
                            @endif
                        </dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Refund Amount</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ number_format((float) $salesReturn->refund_amount, 2) }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Bill</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $salesReturn->salesInvoice->invoice_number }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4 lg:col-span-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Notes</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $salesReturn->notes ?: 'Not set' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="surface-panel overflow-hidden">
                <div class="border-b border-slate-200 bg-white p-5">
                    <p class="section-kicker">Return Items</p>
                    <h2 class="mt-1 text-lg font-semibold text-ink-950">Returned Products</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="table-header">
                            <tr>
                                <th class="px-4 py-3">Product</th>
                                <th class="px-4 py-3">Batch</th>
                                <th class="px-4 py-3">Qty</th>
                                <th class="px-4 py-3">Price</th>
                                <th class="px-4 py-3">Discount</th>
                                <th class="px-4 py-3">Tax</th>
                                <th class="px-4 py-3">Restocked</th>
                                <th class="px-4 py-3">Line Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($salesReturn->items as $item)
                                <tr>
                                    <td class="min-w-64 px-4 py-3 align-top">
                                        <p class="font-semibold text-ink-950">{{ $item->product_name_snapshot }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $item->unit_name }}</p>
                                    </td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ $item->batch_number_snapshot ?: 'Not linked' }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ $item->quantity }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ number_format((float) $item->unit_price, 2) }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ number_format((float) $item->discount_amount, 2) }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ number_format((float) $item->line_tax, 2) }}</td>
                                    <td class="px-4 py-3 align-top">
                                        <span class="badge {{ $item->restock_to_inventory ? 'bg-medical-50 text-medical-700' : 'bg-slate-200 text-slate-600' }}">
                                            {{ $item->restock_to_inventory ? 'Yes' : 'No' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 align-top font-semibold text-ink-900">{{ number_format((float) $item->line_total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="grid gap-3 md:grid-cols-4">
                <div class="metric-tile">
                    <p class="metric-label">Subtotal</p>
                    <p class="mt-2 text-xl font-semibold text-ink-950">{{ number_format((float) $salesReturn->subtotal_amount, 2) }}</p>
                </div>
                <div class="metric-tile">
                    <p class="metric-label">Discount</p>
                    <p class="mt-2 text-xl font-semibold text-ink-950">{{ number_format((float) $salesReturn->discount_amount, 2) }}</p>
                </div>
                <div class="metric-tile">
                    <p class="metric-label">Tax</p>
                    <p class="mt-2 text-xl font-semibold text-ink-950">{{ number_format((float) $salesReturn->tax_amount, 2) }}</p>
                </div>
                <div class="metric-tile border-t-4 border-t-medical-600">
                    <p class="metric-label">Refund Total</p>
                    <p class="mt-2 text-xl font-semibold text-medical-800">{{ number_format((float) $salesReturn->total_amount, 2) }}</p>
                </div>
            </section>
        </div>
    </x-app-shell>
</x-layouts.app>
