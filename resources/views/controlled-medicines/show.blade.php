<x-layouts.app :title="config('app.name').' Controlled Register Entry'">
    <x-app-shell :page-title="$entry->product_name_snapshot" section-label="Controlled Register Detail">
        <x-slot:actions>
            <a href="{{ route('controlled-medicines.index') }}" class="btn-secondary">Back to Register</a>
            @if ($entry->salesInvoice)
                <a href="{{ route('sales-invoices.show', $entry->salesInvoice) }}" class="btn-secondary">Open Bill</a>
            @endif
            @if ($entry->salesReturn)
                <a href="{{ route('sales-returns.show', $entry->salesReturn) }}" class="btn-primary">Open Return</a>
            @endif
        </x-slot>

        <div class="space-y-5">
            <section class="surface-panel p-5">
                <div class="flex flex-col gap-3 border-b border-slate-200 pb-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="section-kicker">Read Only</p>
                        <h2 class="mt-1 text-xl font-semibold text-ink-950">{{ $entry->product_name_snapshot }}</h2>
                        <p class="mt-2 text-sm text-slate-600">{{ $entry->entryTypeLabel() }}</p>
                    </div>
                    <span class="badge {{ $entry->isPositiveEffect() ? 'bg-medical-50 text-medical-700' : 'bg-alert-50 text-alert-700' }}">
                        {{ $entry->quantity_effect }}
                    </span>
                </div>

                <dl class="mt-5 grid gap-4 lg:grid-cols-4">
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Event Date</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $entry->event_date?->format('d M Y') ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Batch</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $entry->batch_number_snapshot ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Patient</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $entry->patient_name_snapshot ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Doctor</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $entry->doctor_name_snapshot ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Prescription</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $entry->prescription_number_snapshot ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Bill</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $entry->invoice_number_snapshot ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Return</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $entry->return_number_snapshot ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4 lg:col-span-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Notes</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $entry->notes ?: 'Not set' }}</dd>
                    </div>
                </dl>
            </section>
        </div>
    </x-app-shell>
</x-layouts.app>
