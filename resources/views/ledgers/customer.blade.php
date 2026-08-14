<x-layouts.app :title="config('app.name').' Customer Ledger'">
<x-app-shell :page-title="$customer->name.' Ledger'" section-label="Customer Ledger">
    <x-slot:actions>
        <a href="{{ route('customers.show', $customer) }}" class="btn-secondary">Customer Detail</a>
        <a href="{{ route('accounting.index') }}" class="btn-primary">Accounting</a>
    </x-slot>

    <div class="space-y-5">
        <section class="surface-panel p-5">
            <p class="section-kicker">Customer statement</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-950">{{ $customer->name }}</h1>
            <p class="mt-2 text-sm text-slate-600">Credits reduce the amount owed and record store-credit history. Opening balance is maintained on the customer profile.</p>
            <div class="mt-5 grid gap-3 md:grid-cols-3"><div class="metric-tile"><p class="metric-label">Opening Balance</p><p class="mt-2 text-2xl font-semibold text-ink-950">{{ $customer->opening_balance }}</p></div><div class="metric-tile"><p class="metric-label">Debits</p><p class="mt-2 text-2xl font-semibold text-ink-950">{{ $statement['debit_total'] }}</p></div><div class="metric-tile border-t-4 border-t-medical-600"><p class="metric-label">Derived Balance</p><p class="mt-2 text-2xl font-semibold text-medical-700">{{ $statement['balance'] }}</p></div></div>
        </section>
        <section class="surface-panel overflow-hidden"><div class="border-b border-slate-200 bg-white p-5"><p class="section-kicker">Immutable entries</p><h2 class="mt-1 text-lg font-semibold text-ink-950">Customer ledger history</h2></div><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-left text-sm"><thead class="table-header"><tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Description</th><th class="px-4 py-3 text-right">Debit</th><th class="px-4 py-3 text-right">Credit</th><th class="px-4 py-3">Source</th></tr></thead><tbody class="divide-y divide-slate-100 bg-white">@forelse ($statement['entries'] as $entry)<tr><td class="px-4 py-3 text-slate-700">{{ $entry->entry_date?->format('d M Y') }}</td><td class="px-4 py-3 capitalize text-slate-700">{{ str_replace('_', ' ', $entry->entry_type) }}</td><td class="px-4 py-3 font-semibold text-ink-950">{{ $entry->description }}</td><td class="px-4 py-3 text-right text-slate-700">{{ $entry->debit }}</td><td class="px-4 py-3 text-right text-slate-700">{{ $entry->credit }}</td><td class="px-4 py-3 text-slate-600">{{ class_basename($entry->source_type) }} #{{ $entry->source_id }}</td></tr>@empty<tr><td colspan="6" class="px-4 py-10 text-center text-sm font-medium text-slate-500">No posted customer ledger entries.</td></tr>@endforelse</tbody></table></div></section>
    </div>
</x-app-shell>
</x-layouts.app>
