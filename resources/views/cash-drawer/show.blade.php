<x-layouts.app :title="config('app.name').' Cash Drawer Shift'">
    <x-app-shell :page-title="$cashDrawerShift->shift_number" section-label="Cash Drawer Detail">
        <x-slot:actions><a href="{{ route('cash-drawer.index') }}" class="btn-secondary">Back to Cash Drawer</a></x-slot>

        <div class="space-y-5">
            <section class="surface-panel p-5">
                <div class="flex flex-col gap-3 border-b border-slate-200 pb-4 sm:flex-row sm:items-start sm:justify-between">
                    <div><p class="section-kicker">Shift Reconciliation</p><h2 class="mt-1 text-xl font-semibold text-ink-950">{{ $cashDrawerShift->shift_number }}</h2><p class="mt-2 text-sm text-slate-600">Opened by {{ $cashDrawerShift->openedBy?->name }} on {{ $cashDrawerShift->opened_at?->format('d M Y, h:i A') }}</p></div>
                    <span class="badge {{ $cashDrawerShift->isOpen() ? 'bg-medical-50 text-medical-700' : 'bg-slate-200 text-slate-600' }}">{{ ucfirst($cashDrawerShift->status) }}</span>
                </div>
                <dl class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="metric-tile"><dt class="metric-label">Opening Float</dt><dd class="mt-2 text-lg font-semibold text-ink-950">{{ number_format((float) $totals['opening_float'], 2) }}</dd></div>
                    <div class="metric-tile"><dt class="metric-label">Cash Sales</dt><dd class="mt-2 text-lg font-semibold text-medical-700">{{ number_format((float) $totals['cash_sales_amount'], 2) }}</dd></div>
                    <div class="metric-tile"><dt class="metric-label">Cash Refunds</dt><dd class="mt-2 text-lg font-semibold text-red-700">{{ number_format((float) $totals['cash_refunds_amount'], 2) }}</dd></div>
                    <div class="metric-tile"><dt class="metric-label">Expected Closing Cash</dt><dd class="mt-2 text-lg font-semibold text-pharma-700">{{ number_format((float) $totals['expected_closing_cash'], 2) }}</dd></div>
                    <div class="metric-tile"><dt class="metric-label">Cash In</dt><dd class="mt-2 text-lg font-semibold text-care-700">{{ number_format((float) $totals['cash_in_amount'], 2) }}</dd></div>
                    <div class="metric-tile"><dt class="metric-label">Cash Out</dt><dd class="mt-2 text-lg font-semibold text-amber-700">{{ number_format((float) $totals['cash_out_amount'], 2) }}</dd></div>
                    <div class="metric-tile"><dt class="metric-label">Counted Cash</dt><dd class="mt-2 text-lg font-semibold text-ink-950">{{ $cashDrawerShift->counted_closing_cash !== null ? number_format((float) $cashDrawerShift->counted_closing_cash, 2) : 'Pending' }}</dd></div>
                    <div class="metric-tile border-t-4 border-t-amber-500"><dt class="metric-label">Variance</dt><dd class="mt-2 text-lg font-semibold {{ (float) ($cashDrawerShift->variance_amount ?? 0) === 0.0 ? 'text-ink-900' : 'text-amber-700' }}">{{ $cashDrawerShift->variance_amount !== null ? number_format((float) $cashDrawerShift->variance_amount, 2) : 'Pending' }}</dd></div>
                </dl>
                @if ($cashDrawerShift->opening_notes || $cashDrawerShift->closing_notes)
                    <dl class="mt-5 grid gap-3 border-t border-slate-200 pt-5 sm:grid-cols-2">
                        <div><dt class="metric-label">Opening Notes</dt><dd class="mt-2 text-sm text-slate-700">{{ $cashDrawerShift->opening_notes ?: 'Not recorded' }}</dd></div>
                        <div><dt class="metric-label">Closing Notes</dt><dd class="mt-2 text-sm text-slate-700">{{ $cashDrawerShift->closing_notes ?: 'Not recorded' }}</dd></div>
                    </dl>
                @endif
            </section>

            <section class="surface-panel overflow-hidden">
                <div class="border-b border-slate-200 bg-white p-5"><p class="section-kicker">Cash Movements</p><h2 class="mt-1 text-lg font-semibold text-ink-950">Manual Entries</h2></div>
                <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-left text-sm"><thead class="table-header"><tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Amount</th><th class="px-4 py-3">Reason</th><th class="px-4 py-3">Recorded By</th></tr></thead><tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($cashDrawerShift->entries as $entry)
                        <tr><td class="px-4 py-3 align-top text-slate-700">{{ $entry->created_at?->format('d M Y, h:i A') }}</td><td class="px-4 py-3 align-top"><span class="badge {{ $entry->entry_type === 'cash_in' ? 'bg-care-50 text-care-700' : 'bg-amber-50 text-amber-700' }}">{{ $entry->entry_type === 'cash_in' ? 'Cash In' : 'Cash Out' }}</span></td><td class="px-4 py-3 align-top font-semibold text-ink-900">{{ number_format((float) $entry->amount, 2) }}</td><td class="px-4 py-3 align-top text-slate-700">{{ $entry->reason }}</td><td class="px-4 py-3 align-top text-slate-700">{{ $entry->createdBy?->name ?: 'System' }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-sm font-medium text-slate-500">No manual movements recorded.</td></tr>
                    @endforelse
                </tbody></table></div>
            </section>

            <section class="grid gap-5 xl:grid-cols-2">
                <div class="surface-panel overflow-hidden"><div class="border-b border-slate-200 bg-white p-5"><p class="section-kicker">Cash Sales</p><h2 class="mt-1 text-lg font-semibold text-ink-950">Linked Bills</h2></div><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-left text-sm"><thead class="table-header"><tr><th class="px-4 py-3">Bill</th><th class="px-4 py-3">Date</th><th class="px-4 py-3 text-right">Amount</th></tr></thead><tbody class="divide-y divide-slate-100 bg-white">@forelse ($cashDrawerShift->salesInvoices as $invoice)<tr><td class="px-4 py-3"><a class="font-semibold text-medical-700 hover:underline" href="{{ route('sales-invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td><td class="px-4 py-3 text-slate-700">{{ $invoice->invoice_date?->format('d M Y') }}</td><td class="px-4 py-3 text-right font-semibold text-ink-900">{{ number_format((float) $invoice->total_amount, 2) }}</td></tr>@empty<tr><td colspan="3" class="px-4 py-10 text-center text-sm font-medium text-slate-500">No linked cash bills.</td></tr>@endforelse</tbody></table></div></div>
                <div class="surface-panel overflow-hidden"><div class="border-b border-slate-200 bg-white p-5"><p class="section-kicker">Cash Refunds</p><h2 class="mt-1 text-lg font-semibold text-ink-950">Linked Returns</h2></div><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-left text-sm"><thead class="table-header"><tr><th class="px-4 py-3">Return</th><th class="px-4 py-3">Date</th><th class="px-4 py-3 text-right">Amount</th></tr></thead><tbody class="divide-y divide-slate-100 bg-white">@forelse ($cashDrawerShift->salesReturns as $return)<tr><td class="px-4 py-3"><a class="font-semibold text-medical-700 hover:underline" href="{{ route('sales-returns.show', $return) }}">{{ $return->return_number }}</a></td><td class="px-4 py-3 text-slate-700">{{ $return->return_date?->format('d M Y') }}</td><td class="px-4 py-3 text-right font-semibold text-ink-900">{{ number_format((float) $return->refund_amount, 2) }}</td></tr>@empty<tr><td colspan="3" class="px-4 py-10 text-center text-sm font-medium text-slate-500">No linked cash refunds.</td></tr>@endforelse</tbody></table></div></div>
            </section>
        </div>
    </x-app-shell>
</x-layouts.app>
