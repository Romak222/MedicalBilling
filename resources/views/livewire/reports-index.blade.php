<x-app-shell page-title="Reports" section-label="Operational Reporting">
    <x-slot:actions>
        <a href="{{ route('reports.controlled-medicines.csv', ['from' => $fromDate, 'to' => $toDate]) }}" class="btn-secondary">Export Controlled Register</a>
        <a href="{{ route('reports.gst.index') }}" class="btn-secondary">GST Reports</a>
        <a href="{{ route('status') }}" class="btn-primary">Dashboard</a>
    </x-slot>

    <div class="space-y-5">
        @if (session('status'))
            <div class="surface-panel border-medical-100 bg-medical-50 px-4 py-3 text-sm font-semibold text-medical-800">
                {{ session('status') }}
            </div>
        @endif

        <form wire:submit="applyFilters" class="surface-panel p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="section-kicker">Report period</p>
                    <h2 class="mt-1 text-lg font-semibold text-ink-950">Choose an operational date range</h2>
                    <p class="mt-1 text-sm text-slate-600">Sales, returns, controlled-medicine events, and cash shifts use this period. Refill status and current inventory are live snapshots.</p>
                </div>
                <div class="flex flex-wrap items-end gap-3">
                    <label class="block"><span class="field-label">From</span><input wire:model="fromDate" type="date" class="field-control mt-1"></label>
                    <label class="block"><span class="field-label">To</span><input wire:model="toDate" type="date" class="field-control mt-1">@error('toDate') <span class="field-error">{{ $message }}</span> @enderror</label>
                    <button type="submit" class="btn-primary">Apply</button>
                    <button type="button" wire:click="resetFilters" class="btn-secondary">Reset</button>
                </div>
            </div>
        </form>

        <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <div class="metric-tile border-t-4 border-t-medical-600">
                <p class="metric-label">Gross Sales</p>
                <p class="mt-2 text-2xl font-semibold text-medical-700">{{ number_format((float) $report['sales']['gross_sales'], 2) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $report['sales']['bills'] }} finalized bills</p>
            </div>
            <div class="metric-tile border-t-4 border-t-pharma-600">
                <p class="metric-label">Refunds</p>
                <p class="mt-2 text-2xl font-semibold text-pharma-700">{{ number_format((float) $report['returns']['refunds'], 2) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $report['returns']['documents'] }} finalized returns</p>
            </div>
            <div class="metric-tile border-t-4 border-t-care-600">
                <p class="metric-label">Controlled Events</p>
                <p class="mt-2 text-2xl font-semibold text-care-700">{{ $report['controlled']['entries'] }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $report['controlled']['dispensed'] }} dispensed, {{ $report['controlled']['reversals'] }} reversals</p>
            </div>
            <div class="metric-tile border-t-4 border-t-alert-500">
                <p class="metric-label">Expired Stock</p>
                <p class="mt-2 text-2xl font-semibold text-alert-700">{{ $report['inventory']['expired_batches'] }}</p>
                <p class="mt-1 text-xs text-slate-500">Available batches only</p>
            </div>
            <div class="metric-tile border-t-4 border-t-slate-400">
                <p class="metric-label">Drawer Variance</p>
                <p class="mt-2 text-2xl font-semibold {{ (float) $report['cash_drawer']['variance'] === 0.0 ? 'text-ink-900' : 'text-alert-700' }}">{{ number_format((float) $report['cash_drawer']['variance'], 2) }}</p>
                <p class="mt-1 text-xs text-slate-500">Closed shifts in period</p>
            </div>
        </section>

        <div class="grid gap-5 xl:grid-cols-2">
            <section class="surface-panel overflow-hidden">
                <div class="border-b border-slate-200 bg-white p-5">
                    <p class="section-kicker">Sales analysis</p>
                    <h2 class="mt-1 text-lg font-semibold text-ink-950">Payment mix</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="table-header"><tr><th class="px-4 py-3">Payment method</th><th class="px-4 py-3">Bills</th><th class="px-4 py-3 text-right">Amount</th></tr></thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($report['sales']['payment_mix'] as $payment)
                                <tr><td class="px-4 py-3 font-semibold text-ink-950">{{ ucfirst($payment->payment_method ?: 'Not set') }}</td><td class="px-4 py-3 text-slate-700">{{ $payment->bill_count }}</td><td class="px-4 py-3 text-right font-semibold text-ink-900">{{ number_format((float) $payment->amount, 2) }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="px-4 py-10 text-center text-sm font-medium text-slate-500">No finalized sales in this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="grid gap-3 border-t border-slate-200 bg-slate-50/60 p-4 sm:grid-cols-3">
                    <div><p class="metric-label">Tax</p><p class="mt-1 font-semibold text-ink-900">{{ number_format((float) $report['sales']['tax'], 2) }}</p></div>
                    <div><p class="metric-label">Discounts</p><p class="mt-1 font-semibold text-ink-900">{{ number_format((float) $report['sales']['discounts'], 2) }}</p></div>
                    <div><p class="metric-label">Cancelled</p><p class="mt-1 font-semibold text-ink-900">{{ $report['sales']['cancelled_bills'] }}</p></div>
                </div>
            </section>

            <section class="surface-panel overflow-hidden">
                <div class="border-b border-slate-200 bg-white p-5">
                    <p class="section-kicker">Sales analysis</p>
                    <h2 class="mt-1 text-lg font-semibold text-ink-950">Top products by billed value</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="table-header"><tr><th class="px-4 py-3">Product</th><th class="px-4 py-3">Quantity</th><th class="px-4 py-3 text-right">Value</th></tr></thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($report['sales']['top_products'] as $product)
                                <tr><td class="px-4 py-3 font-semibold text-ink-950">{{ $product->product_name_snapshot }}</td><td class="px-4 py-3 text-slate-700">{{ $product->quantity }}</td><td class="px-4 py-3 text-right font-semibold text-ink-900">{{ number_format((float) $product->amount, 2) }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="px-4 py-10 text-center text-sm font-medium text-slate-500">No product sales in this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <section class="surface-panel overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-slate-200 bg-white p-5 sm:flex-row sm:items-end sm:justify-between">
                <div><p class="section-kicker">Inventory watch</p><h2 class="mt-1 text-lg font-semibold text-ink-950">Next expiring available batches</h2></div>
                <div class="text-sm text-slate-600">{{ $report['inventory']['available_batches'] }} available batches / {{ $report['inventory']['available_quantity'] }} units</div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="table-header"><tr><th class="px-4 py-3">Product</th><th class="px-4 py-3">Batch</th><th class="px-4 py-3">Expiry</th><th class="px-4 py-3">Available</th><th class="px-4 py-3 text-right">Sale rate</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($report['inventory']['next_expiring'] as $batch)
                            <tr><td class="px-4 py-3 font-semibold text-ink-950">{{ $batch->product?->name ?: 'Deleted product' }}</td><td class="px-4 py-3 text-slate-700">{{ $batch->batch_number }}</td><td class="px-4 py-3 text-slate-700">{{ $batch->expires_on?->format('d M Y') }}</td><td class="px-4 py-3 text-slate-700">{{ $batch->available_quantity }}</td><td class="px-4 py-3 text-right text-slate-700">{{ $batch->sale_rate === null ? 'Not set' : number_format((float) $batch->sale_rate, 2) }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-sm font-medium text-slate-500">No available batches with a future expiry date.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="grid gap-5 xl:grid-cols-2">
            <section class="surface-panel overflow-hidden">
                <div class="flex flex-col gap-2 border-b border-slate-200 bg-white p-5 sm:flex-row sm:items-end sm:justify-between"><div><p class="section-kicker">Regulated activity</p><h2 class="mt-1 text-lg font-semibold text-ink-950">Controlled-medicine register</h2></div><div class="text-sm text-slate-600">Net quantity effect: {{ $report['controlled']['net_quantity'] }}</div></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="table-header"><tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Product</th><th class="px-4 py-3">Patient</th><th class="px-4 py-3">Entry</th><th class="px-4 py-3">Qty</th></tr></thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($report['controlled']['latest'] as $entry)
                                <tr><td class="px-4 py-3 text-slate-700">{{ $entry->event_date?->format('d M Y') }}</td><td class="px-4 py-3 font-semibold text-ink-950">{{ $entry->product_name_snapshot }}</td><td class="px-4 py-3 text-slate-700">{{ $entry->patient_name_snapshot ?: 'No patient' }}</td><td class="px-4 py-3"><span class="badge {{ $entry->isPositiveEffect() ? 'bg-medical-50 text-medical-700' : 'bg-alert-50 text-alert-700' }}">{{ $entry->entryTypeLabel() }}</span></td><td class="px-4 py-3 font-semibold text-slate-700">{{ $entry->quantity_effect }}</td></tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-10 text-center text-sm font-medium text-slate-500">No controlled-medicine events in this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="surface-panel p-5">
                <p class="section-kicker">Patient support</p>
                <h2 class="mt-1 text-lg font-semibold text-ink-950">Current refill workload</h2>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <div class="metric-tile"><p class="metric-label">Tracked</p><p class="mt-2 text-2xl font-semibold text-ink-950">{{ $report['refills']['tracked'] }}</p></div>
                    <div class="metric-tile border-t-4 border-t-alert-500"><p class="metric-label">Overdue</p><p class="mt-2 text-2xl font-semibold text-alert-700">{{ $report['refills']['overdue'] }}</p></div>
                    <div class="metric-tile border-t-4 border-t-medical-600"><p class="metric-label">Due Soon</p><p class="mt-2 text-2xl font-semibold text-medical-700">{{ $report['refills']['due'] }}</p></div>
                    <div class="metric-tile border-t-4 border-t-care-600"><p class="metric-label">Pending</p><p class="mt-2 text-2xl font-semibold text-care-700">{{ $report['refills']['pending'] }}</p></div>
                </div>
                <a href="{{ route('prescription-refills.index') }}" class="btn-secondary mt-5">Open Refill Tracker</a>
            </section>
        </div>

        <section class="surface-panel p-5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div><p class="section-kicker">Cash control</p><h2 class="mt-1 text-lg font-semibold text-ink-950">Drawer reconciliation summary</h2></div>
                <a href="{{ route('cash-drawer.index') }}" class="btn-secondary">Open Cash Drawer</a>
            </div>
            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <div class="metric-tile"><p class="metric-label">Shifts</p><p class="mt-2 text-xl font-semibold text-ink-950">{{ $report['cash_drawer']['shifts'] }}</p></div>
                <div class="metric-tile"><p class="metric-label">Open</p><p class="mt-2 text-xl font-semibold text-medical-700">{{ $report['cash_drawer']['open_shifts'] }}</p></div>
                <div class="metric-tile"><p class="metric-label">Cash Sales</p><p class="mt-2 text-xl font-semibold text-ink-950">{{ number_format((float) $report['cash_drawer']['cash_sales'], 2) }}</p></div>
                <div class="metric-tile"><p class="metric-label">Cash Refunds</p><p class="mt-2 text-xl font-semibold text-ink-950">{{ number_format((float) $report['cash_drawer']['refunds'], 2) }}</p></div>
                <div class="metric-tile border-t-4 border-t-alert-500"><p class="metric-label">Variance</p><p class="mt-2 text-xl font-semibold {{ (float) $report['cash_drawer']['variance'] === 0.0 ? 'text-ink-900' : 'text-alert-700' }}">{{ number_format((float) $report['cash_drawer']['variance'], 2) }}</p></div>
            </div>
        </section>
    </div>
</x-app-shell>
