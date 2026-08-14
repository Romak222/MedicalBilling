<x-layouts.app :title="config('app.name').' Purchase Order Detail'">
    <x-app-shell :page-title="$purchaseOrder->order_number" section-label="Purchase Order Detail">
        <x-slot:actions>
            <a href="{{ route('purchase-orders.index') }}" class="btn-secondary">Back to Orders</a>
            @if (auth()->user()?->hasPermission('purchases.manage') && $purchaseOrder->status !== 'cancelled')
                <a href="{{ route('purchase-orders.edit', $purchaseOrder) }}" class="btn-primary">Edit Order</a>
            @endif
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
                        <h2 class="mt-1 text-xl font-semibold text-ink-950">{{ $purchaseOrder->order_number }}</h2>
                        <p class="mt-2 text-sm text-slate-600">{{ $purchaseOrder->supplier?->name ?: $purchaseOrder->supplier_name_snapshot }}</p>
                    </div>
                    <span class="badge {{ $purchaseOrder->status === 'draft' ? 'bg-care-50 text-care-700' : ($purchaseOrder->status === 'sent' ? 'bg-alert-50 text-alert-700' : 'bg-slate-200 text-slate-600') }}">
                        {{ ucfirst($purchaseOrder->status) }}
                    </span>
                </div>

                <dl class="mt-5 grid gap-4 lg:grid-cols-4">
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Reference</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $purchaseOrder->reference_number ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Order Date</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $purchaseOrder->ordered_on?->format('d M Y') }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Expected Date</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $purchaseOrder->expected_on?->format('d M Y') ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Payment Terms</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $purchaseOrder->payment_terms_days === null ? 'Not set' : $purchaseOrder->payment_terms_days.' days' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4 lg:col-span-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Notes</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $purchaseOrder->notes ?: 'Not set' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="surface-panel overflow-hidden">
                <div class="border-b border-slate-200 bg-white p-5">
                    <p class="section-kicker">Items</p>
                    <h2 class="mt-1 text-lg font-semibold text-ink-950">Ordered Products</h2>
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
                                <th class="px-4 py-3">Tax</th>
                                <th class="px-4 py-3">Line Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($purchaseOrder->items as $item)
                                <tr>
                                    <td class="min-w-64 px-4 py-3 align-top">
                                        <p class="font-semibold text-ink-950">{{ $item->product_name_snapshot }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $item->product?->sku ?: 'No SKU' }}</p>
                                    </td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ $item->unit_name }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ $item->quantity }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ $item->free_quantity }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ number_format((float) $item->unit_cost, 2) }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ number_format((float) $item->discount_amount, 2) }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ $item->tax_rate_percent }}%</td>
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
                    <p class="mt-2 text-xl font-semibold text-ink-950">{{ number_format((float) $purchaseOrder->subtotal_amount, 2) }}</p>
                </div>
                <div class="metric-tile">
                    <p class="metric-label">Discount</p>
                    <p class="mt-2 text-xl font-semibold text-ink-950">{{ number_format((float) $purchaseOrder->discount_amount, 2) }}</p>
                </div>
                <div class="metric-tile">
                    <p class="metric-label">Tax</p>
                    <p class="mt-2 text-xl font-semibold text-ink-950">{{ number_format((float) $purchaseOrder->tax_amount, 2) }}</p>
                </div>
                <div class="metric-tile border-t-4 border-t-medical-600">
                    <p class="metric-label">Total</p>
                    <p class="mt-2 text-xl font-semibold text-medical-800">{{ number_format((float) $purchaseOrder->total_amount, 2) }}</p>
                </div>
            </section>
        </div>
    </x-app-shell>
</x-layouts.app>
