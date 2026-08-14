<x-layouts.app :title="config('app.name').' Supplier Payments'">
    <x-app-shell :page-title="$supplier->name.' Payments'" section-label="Supplier Payments">
        <x-slot:actions>
            <a href="{{ route('suppliers.show', $supplier) }}" class="btn-secondary">Supplier Detail</a>
            <a href="{{ route('suppliers.ledger', $supplier) }}" class="btn-secondary">Ledger</a>
            @if ($canManage)
                <a href="{{ route('supplier-payments.create', $supplier) }}" class="btn-primary">Record Payment</a>
            @endif
        </x-slot>

        <div class="space-y-5">
            <section class="grid gap-3 md:grid-cols-3"><div class="metric-tile"><p class="metric-label">Derived Payable</p><p class="mt-2 text-3xl font-semibold text-alert-700">{{ $statement['balance'] }}</p></div><div class="metric-tile"><p class="metric-label">Posted Payments</p><p class="mt-2 text-3xl font-semibold text-medical-700">{{ $payments->count() }}</p></div><div class="metric-tile"><p class="metric-label">Paid in History</p><p class="mt-2 text-3xl font-semibold text-ink-950">{{ number_format((float) $payments->sum('amount'), 2) }}</p></div></section>
            <section class="surface-panel overflow-hidden"><div class="border-b border-slate-200 bg-white p-5"><p class="section-kicker">Immutable payment documents</p><h2 class="mt-1 text-lg font-semibold text-ink-950">Supplier settlement history</h2></div><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-left text-sm"><thead class="table-header"><tr><th class="px-4 py-3">Payment</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Method</th><th class="px-4 py-3">Reference</th><th class="px-4 py-3 text-right">Amount</th><th class="px-4 py-3 text-right">Action</th></tr></thead><tbody class="divide-y divide-slate-100 bg-white">@forelse ($payments as $payment)<tr><td class="px-4 py-3"><a href="{{ route('supplier-payments.show', [$supplier, $payment]) }}" class="font-semibold text-medical-700 hover:underline">{{ $payment->payment_number }}</a><p class="mt-1 text-xs text-slate-500">{{ ucfirst($payment->status) }}</p></td><td class="px-4 py-3 text-slate-700">{{ $payment->payment_date?->format('d M Y') }}</td><td class="px-4 py-3 capitalize text-slate-700">{{ str_replace('_', ' ', $payment->payment_method) }}</td><td class="px-4 py-3 text-slate-700">{{ $payment->reference ?: 'Not set' }}</td><td class="px-4 py-3 text-right font-semibold text-ink-900">{{ $payment->amount }}</td><td class="px-4 py-3 text-right"><a href="{{ route('supplier-payments.show', [$supplier, $payment]) }}" class="btn-secondary">View</a></td></tr>@empty<tr><td colspan="6" class="px-4 py-12 text-center text-sm font-medium text-slate-500">No supplier payments have been posted.</td></tr>@endforelse</tbody></table></div></section>
        </div>
    </x-app-shell>
</x-layouts.app>
