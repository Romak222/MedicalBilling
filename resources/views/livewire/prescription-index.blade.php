<x-app-shell page-title="Prescriptions" section-label="Prescription Register">
    <x-slot:actions>
        @if (session('status'))
            <span class="badge bg-medical-50 text-medical-700">{{ session('status') }}</span>
        @endif

        @if ($canManage)
            <a href="{{ route('prescriptions.create') }}" class="btn-primary">
                Add New Prescription
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
                <p class="metric-label">Archived</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-slate-500">{{ $stats['archived'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-alert-500">
                <p class="metric-label">Open</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-alert-700">{{ $stats['open'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-pharma-600">
                <p class="metric-label">Dispensed</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-pharma-700">{{ $stats['dispensed'] }}</p>
            </div>
        </section>

        <section class="surface-panel overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-slate-200/80 bg-white p-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="inline-flex w-fit flex-wrap gap-1 rounded-lg bg-slate-100 p-1">
                    @foreach (['open' => 'Open', 'partial' => 'Partial', 'dispensed' => 'Dispensed', 'all' => 'All Active', 'archived' => 'Archived'] as $value => $label)
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
                    placeholder="Search prescription, patient, doctor, phone"
                    class="field-control xl:max-w-md"
                >
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="table-header">
                        <tr>
                            <th class="px-4 py-3">Prescription</th>
                            <th class="px-4 py-3">Patient</th>
                            <th class="px-4 py-3">Doctor</th>
                            <th class="px-4 py-3">Lines</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($prescriptions as $item)
                            <tr class="transition hover:bg-care-50/60 {{ $item->is_active ? 'bg-white' : 'bg-slate-50/70' }}">
                                <td class="min-w-56 px-4 py-3 align-top">
                                    <p class="font-semibold text-ink-950">{{ $item->prescription_number }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $item->prescription_date?->format('d M Y') ?: 'No date' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $item->attachment_original_name ?: 'No attachment' }}</p>
                                </td>
                                <td class="min-w-56 px-4 py-3 align-top text-slate-700">
                                    <p>{{ $item->patient_name_snapshot ?: $item->patient?->full_name ?: 'No patient' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $item->patient_phone_snapshot ?: $item->patient?->phone ?: 'No phone' }}</p>
                                </td>
                                <td class="min-w-52 px-4 py-3 align-top text-slate-700">
                                    <p>{{ $item->doctor_name_snapshot ?: $item->doctor?->name ?: 'No doctor' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $item->doctor?->clinic_name ?: 'No clinic' }}</p>
                                </td>
                                <td class="px-4 py-3 align-top text-slate-700">
                                    <p>{{ $item->items_count }} lines</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $item->sales_invoices_count }} bills</p>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <span class="badge {{ $item->status === 'dispensed' ? 'bg-slate-200 text-slate-700' : 'bg-medical-50 text-medical-700' }}">
                                        {{ ucfirst($item->status) }}{{ ! $item->is_active ? ' / Archived' : '' }}
                                    </span>
                                </td>
                                <td class="min-w-48 px-4 py-3 text-right align-top">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('prescriptions.show', $item) }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50">
                                            View
                                        </a>

                                        @if ($canManage && $item->status !== 'dispensed')
                                            <a href="{{ route('prescriptions.edit', $item) }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50">
                                                Edit
                                            </a>
                                        @endif

                                        @if ($canManage)
                                            @if ($item->is_active)
                                                <button
                                                    type="button"
                                                    wire:click="archivePrescription({{ $item->id }})"
                                                    onclick="return confirm('Archive this prescription from the active register?')"
                                                    class="rounded-md border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-700 shadow-sm transition hover:bg-red-50"
                                                >
                                                    Archive
                                                </button>
                                            @else
                                                <button
                                                    type="button"
                                                    wire:click="restorePrescription({{ $item->id }})"
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
                                <td colspan="6" class="px-4 py-12 text-center text-sm font-medium text-slate-500">No prescriptions found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-shell>
