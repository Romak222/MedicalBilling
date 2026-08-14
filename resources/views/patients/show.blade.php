<x-layouts.app :title="config('app.name').' Patient Detail'">
    <x-app-shell :page-title="$patient->full_name" section-label="Patient Detail">
        <x-slot:actions>
            <a href="{{ route('patients.index') }}" class="btn-secondary">Back to Patients</a>
            @if (auth()->user()?->hasPermission('patients.manage'))
                <a href="{{ route('patients.edit', $patient) }}" class="btn-primary">Edit Patient</a>
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
                        <h2 class="mt-1 text-xl font-semibold text-ink-950">{{ $patient->full_name }}</h2>
                        <p class="mt-2 text-sm text-slate-600">{{ $patient->patient_code ?: 'No patient code' }}</p>
                    </div>
                    <span class="badge {{ $patient->is_active ? 'bg-medical-50 text-medical-700' : 'bg-slate-200 text-slate-600' }}">
                        {{ $patient->is_active ? 'Active' : 'Deleted' }}
                    </span>
                </div>

                <dl class="mt-5 grid gap-4 lg:grid-cols-3">
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Linked Customer</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $patient->customer?->name ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Phone</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $patient->phone ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Email</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $patient->email ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Date of Birth</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $patient->date_of_birth?->format('d M Y') ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Gender</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $patient->gender ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Primary Doctor</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">
                            @if ($patient->doctor)
                                <a href="{{ route('doctors.show', $patient->doctor) }}" class="text-medical-700 underline-offset-2 hover:underline">{{ $patient->doctor->name }}</a>
                            @else
                                {{ $patient->primary_doctor_name ?: 'Not set' }}
                            @endif
                        </dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4 lg:col-span-2">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Address</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">
                            {{ $patient->address_line_1 ?: 'Not set' }}
                            @if ($patient->address_line_2)
                                , {{ $patient->address_line_2 }}
                            @endif
                            @if ($patient->city || $patient->state || $patient->postal_code)
                                <span class="block pt-1 text-slate-700">{{ $patient->city }}{{ $patient->state ? ', '.$patient->state : '' }} {{ $patient->postal_code }}</span>
                            @endif
                        </dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Billing History</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $patient->salesInvoices->count() }} bills</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Consents</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">
                            Reminder {{ $patient->reminder_consent ? 'Yes' : 'No' }},
                            WhatsApp {{ $patient->whatsapp_consent ? 'Yes' : 'No' }},
                            SMS {{ $patient->sms_consent ? 'Yes' : 'No' }}
                        </dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Allergies</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $patient->allergies ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4 lg:col-span-2">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Medical Notes</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $patient->medical_notes ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4 lg:col-span-3">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Notes</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $patient->notes ?: 'Not set' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="surface-panel overflow-hidden">
                <div class="border-b border-slate-200 bg-white p-5">
                    <p class="section-kicker">Billing</p>
                    <h2 class="mt-1 text-lg font-semibold text-ink-950">Recent Bills</h2>
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
                            @forelse ($patient->salesInvoices->sortByDesc('invoice_date')->take(10) as $salesInvoice)
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
                                    <td colspan="6" class="px-4 py-10 text-center text-sm font-medium text-slate-500">No bills linked to this patient yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="surface-panel overflow-hidden">
                <div class="border-b border-slate-200 bg-white p-5">
                    <p class="section-kicker">Prescriptions</p>
                    <h2 class="mt-1 text-lg font-semibold text-ink-950">Prescription History</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="table-header">
                            <tr>
                                <th class="px-4 py-3">Prescription</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Doctor</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($patient->prescriptions->sortByDesc('prescription_date')->take(10) as $prescription)
                                <tr>
                                    <td class="px-4 py-3 align-top font-semibold text-ink-950">{{ $prescription->prescription_number }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ $prescription->prescription_date?->format('d M Y') }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ $prescription->doctor_name_snapshot ?: $prescription->doctor?->name ?: 'Not set' }}</td>
                                    <td class="px-4 py-3 align-top">
                                        <span class="badge {{ $prescription->status === 'dispensed' ? 'bg-slate-200 text-slate-600' : 'bg-medical-50 text-medical-700' }}">
                                            {{ ucfirst($prescription->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right align-top">
                                        <a href="{{ route('prescriptions.show', $prescription) }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-sm font-medium text-slate-500">No prescriptions linked to this patient yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </x-app-shell>
</x-layouts.app>
