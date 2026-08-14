<x-layouts.app :title="config('app.name').' Doctor Detail'">
    <x-app-shell :page-title="$doctor->name" section-label="Doctor Detail">
        <x-slot:actions>
            <a href="{{ route('doctors.index') }}" class="btn-secondary">Back to Doctors</a>
            @if (auth()->user()?->hasPermission('doctors.manage'))
                <a href="{{ route('doctors.edit', $doctor) }}" class="btn-primary">Edit Doctor</a>
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
                        <h2 class="mt-1 text-xl font-semibold text-ink-950">{{ $doctor->name }}</h2>
                        <p class="mt-2 text-sm text-slate-600">{{ $doctor->registration_number ?: 'No registration number' }}</p>
                    </div>
                    <span class="badge {{ $doctor->is_active ? 'bg-medical-50 text-medical-700' : 'bg-slate-200 text-slate-600' }}">
                        {{ $doctor->is_active ? 'Active' : 'Deleted' }}
                    </span>
                </div>

                <dl class="mt-5 grid gap-4 lg:grid-cols-3">
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Specialization</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $doctor->specialization ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Clinic</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $doctor->clinic_name ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Patients</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $doctor->patients->count() }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Phone</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $doctor->phone ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Alternate Phone</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $doctor->alternate_phone ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Email</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $doctor->email ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4 lg:col-span-2">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Address</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">
                            {{ $doctor->address_line_1 ?: 'Not set' }}
                            @if ($doctor->address_line_2)
                                , {{ $doctor->address_line_2 }}
                            @endif
                            @if ($doctor->city || $doctor->state || $doctor->postal_code)
                                <span class="block pt-1 text-slate-700">{{ $doctor->city }}{{ $doctor->state ? ', '.$doctor->state : '' }} {{ $doctor->postal_code }}</span>
                            @endif
                        </dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Prescriptions</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $doctor->prescriptions->count() }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4 lg:col-span-3">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Notes</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $doctor->notes ?: 'Not set' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="surface-panel overflow-hidden">
                <div class="border-b border-slate-200 bg-white p-5">
                    <p class="section-kicker">Patients</p>
                    <h2 class="mt-1 text-lg font-semibold text-ink-950">Linked Patient Records</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="table-header">
                            <tr>
                                <th class="px-4 py-3">Patient</th>
                                <th class="px-4 py-3">Customer</th>
                                <th class="px-4 py-3">Contact</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($doctor->patients->sortBy('full_name') as $patient)
                                <tr>
                                    <td class="px-4 py-3 align-top">
                                        <p class="font-semibold text-ink-950">{{ $patient->full_name }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $patient->patient_code ?: 'No patient code' }}</p>
                                    </td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ $patient->customer?->name ?: 'No linked customer' }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">
                                        <p>{{ $patient->phone ?: 'No phone' }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $patient->email ?: 'No email' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-right align-top">
                                        <a href="{{ route('patients.show', $patient) }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-10 text-center text-sm font-medium text-slate-500">No patients linked to this doctor.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="surface-panel overflow-hidden">
                <div class="border-b border-slate-200 bg-white p-5">
                    <p class="section-kicker">Prescriptions</p>
                    <h2 class="mt-1 text-lg font-semibold text-ink-950">Recent Prescriptions</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="table-header">
                            <tr>
                                <th class="px-4 py-3">Prescription</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Patient</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($doctor->prescriptions->sortByDesc('prescription_date')->take(10) as $prescription)
                                <tr>
                                    <td class="px-4 py-3 align-top font-semibold text-ink-950">{{ $prescription->prescription_number }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ $prescription->prescription_date?->format('d M Y') }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700">{{ $prescription->patient_name_snapshot ?: $prescription->patient?->full_name ?: 'Not set' }}</td>
                                    <td class="px-4 py-3 align-top">
                                        <span class="badge {{ $prescription->status === 'dispensed' ? 'bg-slate-200 text-slate-700' : 'bg-medical-50 text-medical-700' }}">
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
                                    <td colspan="5" class="px-4 py-10 text-center text-sm font-medium text-slate-500">No prescriptions linked to this doctor yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </x-app-shell>
</x-layouts.app>
