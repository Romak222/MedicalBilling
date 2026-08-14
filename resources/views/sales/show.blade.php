<x-layouts.app :title="config('app.name').' Sales Invoice Detail'">
    <x-app-shell :page-title="$salesInvoice->invoice_number" section-label="Bill Detail">
        <x-slot:actions>
            <a href="{{ route('sales-invoices.index') }}" class="btn-secondary">Back to Billing</a>
            @if (auth()->user()?->hasPermission('sales.manage') && $salesInvoice->status === 'finalized')
                <a href="{{ route('sales-returns.create', $salesInvoice) }}" class="btn-secondary">Process Return</a>
            @endif
            <a href="{{ route('sales-invoices.receipt', $salesInvoice) }}" class="btn-primary">Print Receipt</a>
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
                        <h2 class="mt-1 text-xl font-semibold text-ink-950">{{ $salesInvoice->invoice_number }}</h2>
                        <p class="mt-2 text-sm text-slate-600">{{ $salesInvoice->customer_name ?: ($salesInvoice->patient_name ?: 'Walk-in customer') }}</p>
                    </div>
                    <span class="badge {{ $salesInvoice->status === 'finalized' ? 'bg-medical-50 text-medical-700' : 'bg-slate-200 text-slate-600' }}">
                        {{ ucfirst($salesInvoice->status) }}
                    </span>
                </div>

                <dl class="mt-5 grid gap-4 lg:grid-cols-4">
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Bill Date</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $salesInvoice->invoice_date?->format('d M Y') }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Phone</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $salesInvoice->customer_phone ?: ($salesInvoice->patient_phone ?: 'Not set') }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Patient</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">
                            @if ($salesInvoice->patient)
                                <a href="{{ route('patients.show', $salesInvoice->patient) }}" class="text-medical-700 underline-offset-2 hover:underline">{{ $salesInvoice->patient_name ?: $salesInvoice->patient->full_name }}</a>
                            @else
                                {{ $salesInvoice->patient_name ?: 'Not set' }}
                            @endif
                        </dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Payment</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ ucfirst($salesInvoice->payment_method ?: 'Not set') }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Cash Drawer Shift</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">
                            @if ($salesInvoice->cashDrawerShift)
                                <a href="{{ route('cash-drawer.show', $salesInvoice->cashDrawerShift) }}" class="text-medical-700 underline-offset-2 hover:underline">{{ $salesInvoice->cashDrawerShift->shift_number }}</a>
                            @elseif ($salesInvoice->payment_method === 'cash')
                                Unassigned
                            @else
                                Not applicable
                            @endif
                        </dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Customer Record</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">
                            @if ($salesInvoice->customer)
                                <a href="{{ route('customers.show', $salesInvoice->customer) }}" class="text-medical-700 underline-offset-2 hover:underline">{{ $salesInvoice->customer_name ?: $salesInvoice->customer->name }}</a>
                            @else
                                {{ $salesInvoice->customer_name ?: 'Walk-in customer' }}
                            @endif
                        </dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Paid / Change</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ number_format((float) $salesInvoice->paid_amount, 2) }} / {{ number_format((float) $salesInvoice->change_amount, 2) }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Doctor</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">
                            @if ($salesInvoice->doctor)
                                <a href="{{ route('doctors.show', $salesInvoice->doctor) }}" class="text-medical-700 underline-offset-2 hover:underline">{{ $salesInvoice->doctor_name ?: $salesInvoice->doctor->name }}</a>
                            @else
                                {{ $salesInvoice->doctor_name ?: 'Not set' }}
                            @endif
                        </dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Prescription</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">
                            @if ($salesInvoice->prescription)
                                <a href="{{ route('prescriptions.show', $salesInvoice->prescription) }}" class="text-medical-700 underline-offset-2 hover:underline">{{ $salesInvoice->prescription_number ?: $salesInvoice->prescription->prescription_number }}</a>
                            @else
                                {{ $salesInvoice->prescription_number ?: 'Not set' }}
                            @endif
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="surface-panel overflow-hidden">
                <div class="border-b border-slate-200 bg-white p-5">
                    <p class="section-kicker">Items</p>
                    <h2 class="mt-1 text-lg font-semibold text-ink-950">Sold Batches</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="table-header">
                            <tr>
                                <th class="px-4 py-3">Product</th>
                                <th class="px-4 py-3">Batch</th>
                                <th class="px-4 py-3">RX Line</th>
                                <th class="px-4 py-3">Expiry</th>
                                <th class="px-4 py-3">Qty</th>
                                <th class="px-4 py-3">Price</th>
                                <th class="px-4 py-3">Discount</th>
                                <th class="px-4 py-3">Tax</th>
                                <th class="px-4 py-3">Line Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($salesInvoice->items as $item)
                                <tr>
                                    <td class="min-w-64 px-4 py-3 align-top">
                                        <p class="font-semibold text-ink-950">{{ $item->product_name_snapshot }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $item->unit_name }}</p>
                                    </td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ $item->batch_number_snapshot }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ $item->prescriptionItem?->medicine_name_snapshot ?: 'Not linked' }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ $item->expires_on_snapshot?->format('d M Y') }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ $item->quantity }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ number_format((float) $item->unit_price, 2) }}</td>
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
                    <p class="mt-2 text-xl font-semibold text-ink-950">{{ number_format((float) $salesInvoice->subtotal_amount, 2) }}</p>
                </div>
                <div class="metric-tile">
                    <p class="metric-label">Discount</p>
                    <p class="mt-2 text-xl font-semibold text-ink-950">{{ number_format((float) $salesInvoice->discount_amount, 2) }}</p>
                </div>
                <div class="metric-tile">
                    <p class="metric-label">Tax</p>
                    <p class="mt-2 text-xl font-semibold text-ink-950">{{ number_format((float) $salesInvoice->tax_amount, 2) }}</p>
                </div>
                <div class="metric-tile border-t-4 border-t-medical-600">
                    <p class="metric-label">Total</p>
                    <p class="mt-2 text-xl font-semibold text-medical-800">{{ number_format((float) $salesInvoice->total_amount, 2) }}</p>
                </div>
            </section>

            <section class="surface-panel overflow-hidden">
                <div class="border-b border-slate-200 bg-white p-5">
                    <p class="section-kicker">Returns</p>
                    <h2 class="mt-1 text-lg font-semibold text-ink-950">Return History</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="table-header">
                            <tr>
                                <th class="px-4 py-3">Return</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Refund</th>
                                <th class="px-4 py-3">Items</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($salesInvoice->salesReturns as $salesReturn)
                                <tr>
                                    <td class="px-4 py-3 align-top font-semibold text-ink-950">{{ $salesReturn->return_number }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ $salesReturn->return_date?->format('d M Y') }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ number_format((float) $salesReturn->refund_amount, 2) }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ $salesReturn->items->count() }} items</td>
                                    <td class="px-4 py-3 text-right align-top">
                                        <a href="{{ route('sales-returns.show', $salesReturn) }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-sm font-medium text-slate-500">No returns recorded for this bill.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </x-app-shell>
</x-layouts.app>
