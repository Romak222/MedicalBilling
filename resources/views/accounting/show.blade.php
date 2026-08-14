<x-app-shell page-title="Journal {{ $journalEntry->entry_number }}" section-label="Accounting Detail">
    <x-slot:actions>
        <a href="{{ route('accounting.index') }}" class="btn-secondary">Back to Accounting</a>
    </x-slot>

    <div class="space-y-5">
        <section class="surface-panel p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div><p class="section-kicker">Posted journal</p><h1 class="mt-1 text-2xl font-semibold text-ink-950">{{ $journalEntry->entry_number }}</h1><p class="mt-2 text-sm text-slate-600">{{ $journalEntry->description }}</p></div>
                <dl class="grid grid-cols-2 gap-x-8 gap-y-2 text-sm"><div><dt class="text-slate-500">Date</dt><dd class="font-semibold text-ink-950">{{ $journalEntry->entry_date?->format('d M Y') }}</dd></div><div><dt class="text-slate-500">Type</dt><dd class="font-semibold capitalize text-ink-950">{{ str_replace('_', ' ', $journalEntry->entry_type) }}</dd></div><div><dt class="text-slate-500">Source</dt><dd class="font-semibold text-ink-950">{{ class_basename($journalEntry->source_type) }} #{{ $journalEntry->source_id }}</dd></div><div><dt class="text-slate-500">Status</dt><dd class="font-semibold text-medical-700">{{ ucfirst($journalEntry->status) }}</dd></div></dl>
            </div>
        </section>

        <section class="surface-panel overflow-hidden">
            <div class="border-b border-slate-200 bg-white p-5"><p class="section-kicker">Double-entry lines</p><h2 class="mt-1 text-lg font-semibold text-ink-950">Account movements</h2></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="table-header"><tr><th class="px-4 py-3">Account</th><th class="px-4 py-3">Memo</th><th class="px-4 py-3 text-right">Debit</th><th class="px-4 py-3 text-right">Credit</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($journalEntry->lines as $line)
                            <tr><td class="px-4 py-3"><span class="font-semibold text-ink-950">{{ $line->account->code }}</span><span class="ml-2 text-slate-700">{{ $line->account->name }}</span></td><td class="px-4 py-3 text-slate-600">{{ $line->memo }}</td><td class="px-4 py-3 text-right font-semibold text-ink-950">{{ $line->debit }}</td><td class="px-4 py-3 text-right font-semibold text-ink-950">{{ $line->credit }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-shell>
