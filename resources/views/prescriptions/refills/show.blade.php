<x-layouts.app :title="config('app.name').' Refill Detail'">
    <x-app-shell :page-title="$prescriptionItem->medicine_name_snapshot" section-label="Refill Detail">
        <x-slot:actions>
            <a href="{{ route('prescription-refills.index') }}" class="btn-secondary">Back to Refills</a>
            @if ($prescriptionItem->prescription)
                <a href="{{ route('prescriptions.show', $prescriptionItem->prescription) }}" class="btn-secondary">View Prescription</a>
            @endif
        </x-slot>

        @php
            $status = $prescriptionItem->refillStatus();
            $badgeClass = match ($status) {
                'overdue' => 'bg-alert-50 text-alert-700',
                'due' => 'bg-medical-50 text-medical-700',
                'pending' => 'bg-pharma-50 text-pharma-700',
                'completed' => 'bg-emerald-50 text-emerald-700',
                'expired' => 'bg-slate-200 text-slate-700',
                'archived' => 'bg-slate-200 text-slate-600',
                default => 'bg-care-50 text-care-700',
            };
        @endphp

        <div class="space-y-5">
            <section class="surface-panel p-5">
                <div class="flex flex-col gap-3 border-b border-slate-200 pb-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="section-kicker">Tracked Line</p>
                        <h2 class="mt-1 text-xl font-semibold text-ink-950">{{ $prescriptionItem->medicine_name_snapshot }}</h2>
                        <p class="mt-2 text-sm text-slate-600">
                            {{ $prescriptionItem->prescription?->patient_name_snapshot ?: $prescriptionItem->prescription?->patient?->full_name ?: 'No patient linked' }}
                        </p>
                    </div>
                    <span class="badge {{ $badgeClass }}">{{ $prescriptionItem->refillStatusLabel() }}</span>
                </div>

                <dl class="mt-5 grid gap-4 lg:grid-cols-4">
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Prescription</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $prescriptionItem->prescription?->prescription_number ?: 'Not linked' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Doctor</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $prescriptionItem->prescription?->doctor_name_snapshot ?: $prescriptionItem->prescription?->doctor?->name ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Remaining Qty</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $prescriptionItem->remainingQuantity() }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Dispensed Qty</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $prescriptionItem->quantity_dispensed }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Interval</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">
                            {{ $prescriptionItem->refill_interval_days ?: 'Not set' }}
                            @if ($prescriptionItem->refill_interval_days)
                                day{{ $prescriptionItem->refill_interval_days === 1 ? '' : 's' }}
                            @endif
                        </dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Reminder Lead</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $prescriptionItem->refill_reminder_days ?? 0 }} day{{ ($prescriptionItem->refill_reminder_days ?? 0) === 1 ? '' : 's' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Last Dispensed</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $prescriptionItem->last_dispensed_on?->format('d M Y') ?: 'Not dispensed yet' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Next Refill Due</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $prescriptionItem->next_refill_due_on?->format('d M Y') ?: 'Not scheduled yet' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4 lg:col-span-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Dosage</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $prescriptionItem->dosage_instructions ?: 'Not set' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="surface-panel overflow-hidden">
                <div class="border-b border-slate-200 bg-white p-5">
                    <p class="section-kicker">Billing History</p>
                    <h2 class="mt-1 text-lg font-semibold text-ink-950">Dispense Trail</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="table-header">
                            <tr>
                                <th class="px-4 py-3">Bill</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Billed Qty</th>
                                <th class="px-4 py-3">Returned Qty</th>
                                <th class="px-4 py-3">Net Qty</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($prescriptionItem->salesInvoiceItems->sortByDesc(fn ($item) => $item->salesInvoice?->invoice_date?->toDateString() ?? '0000-00-00') as $invoiceItem)
                                @php
                                    $returnedQty = $invoiceItem->salesReturnItems->sum(fn ($returnItem) => (float) $returnItem->quantity);
                                    $netQty = max(0, (float) $invoiceItem->quantity - $returnedQty);
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 align-top font-semibold text-ink-950">{{ $invoiceItem->salesInvoice?->invoice_number ?: 'No bill' }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ $invoiceItem->salesInvoice?->invoice_date?->format('d M Y') ?: 'Not set' }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ number_format((float) $invoiceItem->quantity, 6, '.', '') }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ number_format($returnedQty, 6, '.', '') }}</td>
                                    <td class="px-4 py-3 align-top font-semibold text-ink-900">{{ number_format($netQty, 6, '.', '') }}</td>
                                    <td class="px-4 py-3 align-top">
                                        <span class="badge {{ $invoiceItem->salesInvoice?->status === 'finalized' ? 'bg-medical-50 text-medical-700' : 'bg-slate-200 text-slate-700' }}">
                                            {{ ucfirst($invoiceItem->salesInvoice?->status ?? 'unknown') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right align-top">
                                        @if ($invoiceItem->salesInvoice)
                                            <a href="{{ route('sales-invoices.show', $invoiceItem->salesInvoice) }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50">
                                                View
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-12 text-center text-sm font-medium text-slate-500">No billing records have dispensed this prescription line yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </x-app-shell>
</x-layouts.app>
