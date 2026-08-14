<x-layouts.app :title="config('app.name').' Prescription Detail'">
    <x-app-shell :page-title="$prescription->prescription_number" section-label="Prescription Detail">
        <x-slot:actions>
            <a href="{{ route('prescriptions.index') }}" class="btn-secondary">Back to Prescriptions</a>
            <a href="{{ route('prescription-refills.index') }}" class="btn-secondary">Refill Tracker</a>
            @if ($prescription->attachment_path)
                <a href="{{ route('prescriptions.attachment', $prescription) }}" class="btn-secondary">Download Attachment</a>
            @endif
            @if (auth()->user()?->hasPermission('prescriptions.manage') && $prescription->status !== 'dispensed')
                <a href="{{ route('prescriptions.edit', $prescription) }}" class="btn-primary">Edit Prescription</a>
            @endif
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
                        <h2 class="mt-1 text-xl font-semibold text-ink-950">{{ $prescription->prescription_number }}</h2>
                        <p class="mt-2 text-sm text-slate-600">{{ $prescription->patient_name_snapshot ?: $prescription->patient?->full_name ?: 'Patient not set' }}</p>
                    </div>
                    <span class="badge {{ $prescription->status === 'dispensed' ? 'bg-slate-200 text-slate-700' : 'bg-medical-50 text-medical-700' }}">
                        {{ ucfirst($prescription->status) }}
                    </span>
                </div>

                <dl class="mt-5 grid gap-4 lg:grid-cols-4">
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Patient</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">
                            @if ($prescription->patient)
                                <a href="{{ route('patients.show', $prescription->patient) }}" class="text-medical-700 underline-offset-2 hover:underline">{{ $prescription->patient_name_snapshot ?: $prescription->patient->full_name }}</a>
                            @else
                                {{ $prescription->patient_name_snapshot ?: 'Not set' }}
                            @endif
                        </dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Doctor</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">
                            @if ($prescription->doctor)
                                <a href="{{ route('doctors.show', $prescription->doctor) }}" class="text-medical-700 underline-offset-2 hover:underline">{{ $prescription->doctor_name_snapshot ?: $prescription->doctor->name }}</a>
                            @else
                                {{ $prescription->doctor_name_snapshot ?: 'Not set' }}
                            @endif
                        </dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Prescription Date</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $prescription->prescription_date?->format('d M Y') ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Valid Until</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $prescription->valid_until?->format('d M Y') ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Attachment</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $prescription->attachment_original_name ?: 'Not attached' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Patient Phone</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $prescription->patient_phone_snapshot ?: $prescription->patient?->phone ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4 lg:col-span-2">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Notes</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $prescription->notes ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4 lg:col-span-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Pharmacist Notes</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $prescription->pharmacist_notes ?: 'Not set' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="surface-panel overflow-hidden">
                <div class="border-b border-slate-200 bg-white p-5">
                    <p class="section-kicker">Lines</p>
                    <h2 class="mt-1 text-lg font-semibold text-ink-950">Prescribed Medicines</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="table-header">
                            <tr>
                                <th class="px-4 py-3">Medicine</th>
                                <th class="px-4 py-3">Dosage</th>
                                <th class="px-4 py-3">Prescribed</th>
                                <th class="px-4 py-3">Dispensed</th>
                                <th class="px-4 py-3">Refill</th>
                                <th class="px-4 py-3">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($prescription->items as $item)
                                @php
                                    $refillStatus = $item->refillStatus();
                                    $refillBadgeClass = match ($refillStatus) {
                                        'overdue' => 'bg-alert-50 text-alert-700',
                                        'due' => 'bg-medical-50 text-medical-700',
                                        'pending' => 'bg-pharma-50 text-pharma-700',
                                        'completed' => 'bg-emerald-50 text-emerald-700',
                                        'expired' => 'bg-slate-200 text-slate-700',
                                        'archived' => 'bg-slate-200 text-slate-600',
                                        default => 'bg-care-50 text-care-700',
                                    };
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 align-top">
                                        <p class="font-semibold text-ink-950">{{ $item->medicine_name_snapshot }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $item->unit_name_snapshot ?: 'Unit not set' }}</p>
                                    </td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ $item->dosage_instructions ?: 'Not set' }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ $item->quantity_prescribed }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ $item->quantity_dispensed }}</td>
                                    <td class="min-w-52 px-4 py-3 align-top">
                                        @if ($item->refillTrackingEnabled())
                                            <span class="badge {{ $refillBadgeClass }}">{{ $item->refillStatusLabel() }}</span>
                                            <p class="mt-2 text-xs text-slate-500">
                                                Every {{ $item->refill_interval_days }} day{{ $item->refill_interval_days === 1 ? '' : 's' }}
                                                @if (($item->refill_reminder_days ?? 0) > 0)
                                                    / remind {{ $item->refill_reminder_days }} day{{ $item->refill_reminder_days === 1 ? '' : 's' }} before
                                                @endif
                                            </p>
                                            <p class="mt-1 text-xs text-slate-500">Next due: {{ $item->next_refill_due_on?->format('d M Y') ?: 'Not scheduled yet' }}</p>
                                        @else
                                            <span class="text-slate-500">Not scheduled</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ $item->notes ?: 'Not set' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="surface-panel overflow-hidden">
                <div class="border-b border-slate-200 bg-white p-5">
                    <p class="section-kicker">Billing</p>
                    <h2 class="mt-1 text-lg font-semibold text-ink-950">Linked Bills</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="table-header">
                            <tr>
                                <th class="px-4 py-3">Bill</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Customer</th>
                                <th class="px-4 py-3">Total</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($prescription->salesInvoices->sortByDesc('invoice_date') as $salesInvoice)
                                <tr>
                                    <td class="px-4 py-3 align-top font-semibold text-ink-950">{{ $salesInvoice->invoice_number }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ $salesInvoice->invoice_date?->format('d M Y') }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ $salesInvoice->customer_name ?: 'Walk-in customer' }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ number_format((float) $salesInvoice->total_amount, 2) }}</td>
                                    <td class="px-4 py-3 align-top">
                                        <span class="badge {{ $salesInvoice->status === 'finalized' ? 'bg-medical-50 text-medical-700' : 'bg-slate-200 text-slate-600' }}">
                                            {{ ucfirst($salesInvoice->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right align-top">
                                        <a href="{{ route('sales-invoices.show', $salesInvoice) }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-10 text-center text-sm font-medium text-slate-500">No bills linked to this prescription yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </x-app-shell>
</x-layouts.app>
