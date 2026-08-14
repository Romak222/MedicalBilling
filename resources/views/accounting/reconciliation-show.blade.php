<x-app-shell page-title="Reconciliation {{ $reconciliation->reconciliation_number }}" section-label="Settlement Detail">
    <x-slot:actions>
        <a href="{{ route('accounting.reconciliation.index') }}" class="btn-secondary">Back to Reconciliation</a>
        <a href="{{ route('accounting.index') }}" class="btn-primary">Accounting</a>
    </x-slot>

    <div class="space-y-5">
        <section class="surface-panel p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div><p class="section-kicker">Posted settlement</p><h1 class="mt-1 text-2xl font-semibold text-ink-950">{{ $reconciliation->settlement_reference }}</h1><p class="mt-2 text-sm text-slate-600">{{ $reconciliation->reconciliation_number }}</p></div>
                <span class="badge bg-medical-50 text-medical-700">{{ ucfirst($reconciliation->status) }}</span>
            </div>
            <dl class="mt-5 grid gap-4 md:grid-cols-3">
                <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4"><dt class="text-xs font-semibold uppercase text-slate-500">Method</dt><dd class="mt-2 font-semibold capitalize text-ink-950">{{ $reconciliation->payment_method }}</dd></div>
                <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4"><dt class="text-xs font-semibold uppercase text-slate-500">Billing Period</dt><dd class="mt-2 font-semibold text-ink-950">{{ $reconciliation->period_from?->format('d M Y') }} - {{ $reconciliation->period_to?->format('d M Y') }}</dd></div>
                <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4"><dt class="text-xs font-semibold uppercase text-slate-500">Settlement Date</dt><dd class="mt-2 font-semibold text-ink-950">{{ $reconciliation->settlement_date?->format('d M Y') }}</dd></div>
                <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4"><dt class="text-xs font-semibold uppercase text-slate-500">Expected</dt><dd class="mt-2 font-semibold text-ink-950">{{ $reconciliation->expected_amount }}</dd></div>
                <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4"><dt class="text-xs font-semibold uppercase text-slate-500">Settled</dt><dd class="mt-2 font-semibold text-ink-950">{{ $reconciliation->settled_amount }}</dd></div>
                <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4"><dt class="text-xs font-semibold uppercase text-slate-500">Provider Fee</dt><dd class="mt-2 font-semibold text-ink-950">{{ $reconciliation->fee_amount }}</dd></div>
            </dl>
        </section>

        <section class="surface-panel p-5">
            <p class="section-kicker">Linked journal</p>
            <h2 class="mt-1 text-lg font-semibold text-ink-950">Settlement accounting entry</h2>
            @if ($reconciliation->journalEntry)
                <a href="{{ route('accounting.journal.show', $reconciliation->journalEntry) }}" class="btn-secondary mt-4">{{ $reconciliation->journalEntry->entry_number }}</a>
            @else
                <p class="mt-3 text-sm text-slate-600">No journal entry linked.</p>
            @endif
            @if ($reconciliation->notes)
                <p class="mt-5 border-t border-slate-200 pt-4 text-sm text-slate-700">{{ $reconciliation->notes }}</p>
            @endif
        </section>
    </div>
</x-app-shell>
