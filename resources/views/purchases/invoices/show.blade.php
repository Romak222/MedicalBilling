<x-layouts.app :title="config('app.name').' Purchase Invoice Detail'">
    <x-app-shell :page-title="$purchaseInvoice->invoice_number" section-label="Purchase Invoice Detail">
        <x-slot:actions>
            <a href="{{ route('purchase-invoices.index') }}" class="btn-secondary">Back to Receiving</a>
            @if (auth()->user()?->hasPermission('purchases.manage') && $purchaseInvoice->status === 'finalized')
                <a href="{{ route('purchase-returns.create', $purchaseInvoice) }}" class="btn-primary">Return Stock</a>
            @endif
            @if (auth()->user()?->hasPermission('purchases.manage') && $purchaseInvoice->status === 'draft')
                <a href="{{ route('purchase-invoices.edit', $purchaseInvoice) }}" class="btn-primary">Edit Invoice</a>
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
                        <h2 class="mt-1 text-xl font-semibold text-ink-950">{{ $purchaseInvoice->invoice_number }}</h2>
                        <p class="mt-2 text-sm text-slate-600">{{ $purchaseInvoice->supplier?->name ?: $purchaseInvoice->supplier_name_snapshot }}</p>
                    </div>
                    <span class="badge {{ $purchaseInvoice->status === 'draft' ? 'bg-alert-50 text-alert-700' : ($purchaseInvoice->status === 'finalized' ? 'bg-medical-50 text-medical-700' : 'bg-slate-200 text-slate-600') }}">
                        {{ ucfirst($purchaseInvoice->status) }}
                    </span>
                </div>

                <dl class="mt-5 grid gap-4 lg:grid-cols-4">
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Purchase Order</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $purchaseInvoice->purchaseOrder?->order_number ?: 'Not linked' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Invoice Date</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $purchaseInvoice->invoice_date?->format('d M Y') }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Received Date</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $purchaseInvoice->received_on?->format('d M Y') }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Finalized</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $purchaseInvoice->finalized_at?->format('d M Y H:i') ?: 'Not finalized' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4 lg:col-span-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Notes</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $purchaseInvoice->notes ?: 'Not set' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="surface-panel overflow-hidden">
                <div class="border-b border-slate-200 bg-white p-5">
                    <p class="section-kicker">Batch Items</p>
                    <h2 class="mt-1 text-lg font-semibold text-ink-950">Received Products</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="table-header">
                            <tr>
                                <th class="px-4 py-3">Product</th>
                                <th class="px-4 py-3">Batch</th>
                                <th class="px-4 py-3">Expiry</th>
                                <th class="px-4 py-3">Qty</th>
                                <th class="px-4 py-3">Free</th>
                                <th class="px-4 py-3">MRP</th>
                                <th class="px-4 py-3">Purchase</th>
                                <th class="px-4 py-3">Tax</th>
                                <th class="px-4 py-3">Line Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($purchaseInvoice->items as $item)
                                <tr>
                                    <td class="min-w-64 px-4 py-3 align-top">
                                        <p class="font-semibold text-ink-950">{{ $item->product_name_snapshot }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $item->unit_name }}</p>
                                    </td>
                                    <td class="px-4 py-3 align-top text-slate-700">
                                        <p>{{ $item->batch_number }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $item->productBatch ? 'Batch created' : 'Draft only' }}</p>
                                    </td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ $item->expires_on?->format('d M Y') }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ $item->quantity }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ $item->free_quantity }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ number_format((float) $item->mrp, 2) }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ number_format((float) $item->purchase_rate, 2) }}</td>
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
                    <p class="mt-2 text-xl font-semibold text-ink-950">{{ number_format((float) $purchaseInvoice->subtotal_amount, 2) }}</p>
                </div>
                <div class="metric-tile">
                    <p class="metric-label">Discount</p>
                    <p class="mt-2 text-xl font-semibold text-ink-950">{{ number_format((float) $purchaseInvoice->discount_amount, 2) }}</p>
                </div>
                <div class="metric-tile">
                    <p class="metric-label">Tax</p>
                    <p class="mt-2 text-xl font-semibold text-ink-950">{{ number_format((float) $purchaseInvoice->tax_amount, 2) }}</p>
                </div>
                <div class="metric-tile border-t-4 border-t-medical-600">
                    <p class="metric-label">Total</p>
                    <p class="mt-2 text-xl font-semibold text-medical-800">{{ number_format((float) $purchaseInvoice->total_amount, 2) }}</p>
                </div>
            </section>
        </div>
    </x-app-shell>
</x-layouts.app>
