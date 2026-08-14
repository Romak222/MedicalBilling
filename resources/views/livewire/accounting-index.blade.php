<x-app-shell page-title="Accounting" section-label="Posted Ledger">
    <x-slot:actions>
        <a href="{{ route('reports.index') }}" class="btn-secondary">Reports</a>
        <a href="{{ route('status') }}" class="btn-primary">Dashboard</a>
    </x-slot>

    <div class="space-y-5">
        @if (session('status'))
            <div class="surface-panel border-medical-100 bg-medical-50 px-4 py-3 text-sm font-semibold text-medical-800">{{ session('status') }}</div>
        @endif

        <form wire:submit="applyFilters" class="surface-panel p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="section-kicker">Ledger period</p>
                    <h2 class="mt-1 text-lg font-semibold text-ink-950">Review posted accounting activity</h2>
                    <p class="mt-1 text-sm text-slate-600">Entries are generated from finalized business documents and cannot be silently edited or deleted.</p>
                </div>
                <div class="flex flex-wrap items-end gap-3">
                    <label class="block"><span class="field-label">From</span><input wire:model="fromDate" type="date" class="field-control mt-1"></label>
                    <label class="block"><span class="field-label">To</span><input wire:model="toDate" type="date" class="field-control mt-1">@error('toDate') <span class="field-error">{{ $message }}</span> @enderror</label>
                    <button type="submit" class="btn-primary">Apply</button>
                    <button type="button" wire:click="resetFilters" class="btn-secondary">Reset</button>
                </div>
            </div>
        </form>

        <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <div class="metric-tile border-t-4 border-t-medical-600"><p class="metric-label">Posted Entries</p><p class="mt-2 text-2xl font-semibold text-medical-700">{{ $entryCount }}</p><p class="mt-1 text-xs text-slate-500">In selected period</p></div>
            <div class="metric-tile border-t-4 border-t-pharma-600"><p class="metric-label">Total Debits</p><p class="mt-2 text-2xl font-semibold text-ink-950">{{ $debitTotal }}</p><p class="mt-1 text-xs text-slate-500">Ledger control total</p></div>
            <div class="metric-tile border-t-4 border-t-care-600"><p class="metric-label">Total Credits</p><p class="mt-2 text-2xl font-semibold text-ink-950">{{ $creditTotal }}</p><p class="mt-1 text-xs text-slate-500">Ledger control total</p></div>
            <div class="metric-tile border-t-4 border-t-slate-400"><p class="metric-label">Balance Check</p><p class="mt-2 text-2xl font-semibold {{ $debitTotal === $creditTotal ? 'text-medical-700' : 'text-alert-700' }}">{{ $debitTotal === $creditTotal ? 'Balanced' : 'Review' }}</p><p class="mt-1 text-xs text-slate-500">Every posted entry must balance</p></div>
        </section>

        <section class="surface-panel overflow-hidden">
            <div class="border-b border-slate-200 bg-white p-5">
                <p class="section-kicker">Chart of accounts</p>
                <h2 class="mt-1 text-lg font-semibold text-ink-950">Account activity</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="table-header"><tr><th class="px-4 py-3">Code</th><th class="px-4 py-3">Account</th><th class="px-4 py-3">Type</th><th class="px-4 py-3 text-right">Debits</th><th class="px-4 py-3 text-right">Credits</th><th class="px-4 py-3 text-right">Balance</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($accounts as $summary)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-ink-950">{{ $summary['account']->code }}</td>
                                <td class="px-4 py-3"><p class="font-semibold text-ink-950">{{ $summary['account']->name }}</p><p class="text-xs text-slate-500">{{ $summary['account']->description }}</p></td>
                                <td class="px-4 py-3 capitalize text-slate-700">{{ $summary['account']->account_type }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">{{ $summary['debit'] }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">{{ $summary['credit'] }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-ink-950">{{ $summary['balance'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="surface-panel overflow-hidden">
            <div class="border-b border-slate-200 bg-white p-5">
                <p class="section-kicker">Journal</p>
                <h2 class="mt-1 text-lg font-semibold text-ink-950">Latest posted entries</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="table-header"><tr><th class="px-4 py-3">Entry</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Source</th><th class="px-4 py-3 text-right">Debit / credit</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($entries as $row)
                            <tr>
                                <td class="px-4 py-3"><a href="{{ route('accounting.journal.show', $row['entry']) }}" class="font-semibold text-medical-700 hover:underline">{{ $row['entry']->entry_number }}</a><p class="mt-1 text-xs text-slate-500">{{ $row['entry']->description }}</p></td>
                                <td class="px-4 py-3 text-slate-700">{{ $row['entry']->entry_date?->format('d M Y') }}</td>
                                <td class="px-4 py-3 capitalize text-slate-700">{{ str_replace('_', ' ', $row['entry']->entry_type) }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ class_basename($row['entry']->source_type) }} #{{ $row['entry']->source_id }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-ink-950">{{ $row['debit'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-sm font-medium text-slate-500">No posted entries in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-shell>
