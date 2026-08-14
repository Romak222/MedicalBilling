<x-app-shell page-title="Patients" section-label="Patient Directory">
    <x-slot:actions>
        @if (session('status'))
            <span class="badge bg-medical-50 text-medical-700">{{ session('status') }}</span>
        @endif

        @if ($canManage)
            <a href="{{ route('patients.create') }}" class="btn-primary">
                Add New Patient
            </a>
        @endif
    </x-slot>

    <div class="space-y-5">
        <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <div class="metric-tile border-t-4 border-t-care-600">
                <p class="metric-label">Total</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-care-700">{{ $stats['total'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-medical-600">
                <p class="metric-label">Active</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-medical-700">{{ $stats['active'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-slate-400">
                <p class="metric-label">Deleted</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-slate-500">{{ $stats['inactive'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-pharma-600">
                <p class="metric-label">Linked Customers</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-pharma-700">{{ $stats['linked_customers'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-alert-500">
                <p class="metric-label">With Doctor</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-alert-700">{{ $stats['with_doctor'] }}</p>
            </div>
        </section>

        <section class="surface-panel overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-slate-200/80 bg-white p-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="inline-flex w-fit flex-wrap gap-1 rounded-lg bg-slate-100 p-1">
                    @foreach (['active' => 'Active', 'all' => 'All Patients', 'inactive' => 'Deleted'] as $value => $label)
                        <button
                            type="button"
                            wire:click="$set('statusFilter', '{{ $value }}')"
                            class="rounded-md px-3 py-2 text-sm font-semibold transition {{ $statusFilter === $value ? 'bg-white text-medical-800 shadow-sm' : 'text-slate-600 hover:bg-white/70 hover:text-ink-900' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="Search patient, code, phone, doctor, customer"
                    class="field-control xl:max-w-md"
                >
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="table-header">
                        <tr>
                            <th class="px-4 py-3">Patient</th>
                            <th class="px-4 py-3">Customer Link</th>
                            <th class="px-4 py-3">Contact</th>
                            <th class="px-4 py-3">Doctor</th>
                            <th class="px-4 py-3">Bills</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($patients as $item)
                            <tr class="transition hover:bg-care-50/60 {{ $item->is_active ? 'bg-white' : 'bg-slate-50/70' }}">
                                <td class="min-w-72 px-4 py-3 align-top">
                                    <p class="font-semibold text-ink-950">{{ $item->full_name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $item->patient_code ?: 'No patient code' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        @if ($item->date_of_birth)
                                            DOB {{ $item->date_of_birth->format('d M Y') }}
                                        @else
                                            DOB not set
                                        @endif
                                        @if ($item->gender)
                                            / {{ $item->gender }}
                                        @endif
                                    </p>
                                </td>
                                <td class="min-w-52 px-4 py-3 align-top text-slate-700">
                                    <p>{{ $item->customer?->name ?: 'No linked customer' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $item->customer?->code ?: 'No customer code' }}</p>
                                </td>
                                <td class="min-w-56 px-4 py-3 align-top text-slate-700">
                                    <p>{{ $item->phone ?: 'No phone' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $item->email ?: 'No email' }}</p>
                                </td>
                                <td class="px-4 py-3 align-top text-slate-700">{{ $item->doctor?->name ?: ($item->primary_doctor_name ?: 'Not set') }}</td>
                                <td class="px-4 py-3 align-top text-slate-700">{{ $item->sales_invoices_count }} bills</td>
                                <td class="px-4 py-3 align-top">
                                    <span class="badge {{ $item->is_active ? 'bg-medical-50 text-medical-700' : 'bg-slate-200 text-slate-600' }}">
                                        {{ $item->is_active ? 'Active' : 'Deleted' }}
                                    </span>
                                </td>
                                <td class="min-w-48 px-4 py-3 text-right align-top">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('patients.show', $item) }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50">
                                            View
                                        </a>

                                        @if ($canManage)
                                            <a href="{{ route('patients.edit', $item) }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50">
                                                Edit
                                            </a>

                                            @if ($item->is_active)
                                                <button
                                                    type="button"
                                                    wire:click="deactivatePatient({{ $item->id }})"
                                                    onclick="return confirm('Delete this patient from the active directory?')"
                                                    class="rounded-md border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-700 shadow-sm transition hover:bg-red-50"
                                                >
                                                    Delete
                                                </button>
                                            @else
                                                <button
                                                    type="button"
                                                    wire:click="restorePatient({{ $item->id }})"
                                                    class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50"
                                                >
                                                    Restore
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-sm font-medium text-slate-500">No patients found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-shell>
