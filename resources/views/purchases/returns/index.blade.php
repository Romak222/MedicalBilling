<x-layouts.app :title="config('app.name').' Purchase Returns'">
    <x-app-shell page-title="Purchase Returns" section-label="Supplier Returns">
        <x-slot:actions>
            <a href="{{ route('purchase-invoices.index') }}" class="btn-secondary">Back to Receiving</a>
        </x-slot>

        <div class="space-y-5">
            <section class="grid gap-3 md:grid-cols-3">
                <div class="metric-tile border-t-4 border-t-medical-600"><p class="metric-label">Finalized Returns</p><p class="mt-2 text-3xl font-semibold text-medical-700">{{ $returns->count() }}</p></div>
                <div class="metric-tile border-t-4 border-t-alert-500"><p class="metric-label">Returned Value</p><p class="mt-2 text-3xl font-semibold text-alert-700">{{ number_format((float) $returns->sum('total_amount'), 2) }}</p></div>
                <div class="metric-tile border-t-4 border-t-slate-400"><p class="metric-label">Stock Movements</p><p class="mt-2 text-3xl font-semibold text-slate-700">{{ $returns->sum(fn ($return) => $return->items->count()) }}</p></div>
            </section>

            <section class="surface-panel overflow-hidden">
                <div class="border-b border-slate-200 bg-white p-5"><p class="section-kicker">Immutable return documents</p><h2 class="mt-1 text-lg font-semibold text-ink-950">Supplier return history</h2></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="table-header"><tr><th class="px-4 py-3">Return</th><th class="px-4 py-3">Supplier</th><th class="px-4 py-3">Source Invoice</th><th class="px-4 py-3">Date</th><th class="px-4 py-3 text-right">Total</th><th class="px-4 py-3 text-right">Action</th></tr></thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($returns as $return)
                                <tr><td class="px-4 py-3"><a href="{{ route('purchase-returns.show', $return) }}" class="font-semibold text-medical-700 hover:underline">{{ $return->return_number }}</a><p class="mt-1 text-xs text-slate-500">{{ ucfirst($return->status) }}</p></td><td class="px-4 py-3 text-slate-700">{{ $return->supplier?->name }}</td><td class="px-4 py-3 text-slate-700">{{ $return->purchaseInvoice?->invoice_number }}</td><td class="px-4 py-3 text-slate-700">{{ $return->return_date?->format('d M Y') }}</td><td class="px-4 py-3 text-right font-semibold text-ink-900">{{ number_format((float) $return->total_amount, 2) }}</td><td class="px-4 py-3 text-right"><a href="{{ route('purchase-returns.show', $return) }}" class="btn-secondary">View</a></td></tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-12 text-center text-sm font-medium text-slate-500">No purchase returns have been finalized.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </x-app-shell>
</x-layouts.app>
