<x-app-shell page-title="Refill Tracker" section-label="Prescription Follow-Up">
    <div class="space-y-5">
        <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
            <div class="metric-tile border-t-4 border-t-care-600">
                <p class="metric-label">Tracked</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-care-700">{{ $stats['tracked'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-alert-500">
                <p class="metric-label">Overdue</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-alert-700">{{ $stats['overdue'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-medical-600">
                <p class="metric-label">Due Soon</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-medical-700">{{ $stats['due'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-pharma-600">
                <p class="metric-label">Pending</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-pharma-700">{{ $stats['pending'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-slate-400">
                <p class="metric-label">Upcoming</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-slate-700">{{ $stats['upcoming'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-emerald-500">
                <p class="metric-label">Completed</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-emerald-700">{{ $stats['completed'] }}</p>
            </div>
        </section>

        <section class="surface-panel overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-slate-200/80 bg-white p-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="inline-flex w-fit flex-wrap gap-1 rounded-lg bg-slate-100 p-1">
                    @foreach ([
                        'all' => 'All',
                        'overdue' => 'Overdue',
                        'due' => 'Due Soon',
                        'pending' => 'Pending',
                        'upcoming' => 'Upcoming',
                        'completed' => 'Completed',
                        'expired' => 'Expired',
                        'archived' => 'Archived',
                    ] as $value => $label)
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
                    placeholder="Search patient, prescription, medicine"
                    class="field-control xl:max-w-md"
                >
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="table-header">
                        <tr>
                            <th class="px-4 py-3">Patient</th>
                            <th class="px-4 py-3">Prescription</th>
                            <th class="px-4 py-3">Medicine</th>
                            <th class="px-4 py-3">Remaining</th>
                            <th class="px-4 py-3">Last Dispensed</th>
                            <th class="px-4 py-3">Next Due</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($items as $item)
                            @php
                                $status = $item->refillStatus();
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
                            <tr class="transition hover:bg-care-50/60">
                                <td class="min-w-56 px-4 py-3 align-top">
                                    <p class="font-semibold text-ink-950">{{ $item->prescription?->patient_name_snapshot ?: $item->prescription?->patient?->full_name ?: 'No patient' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $item->prescription?->patient?->patient_code ?: ($item->prescription?->patient_phone_snapshot ?: 'No code') }}</p>
                                </td>
                                <td class="min-w-44 px-4 py-3 align-top text-slate-700">
                                    <p class="font-semibold text-ink-900">{{ $item->prescription?->prescription_number ?: 'Not linked' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $item->prescription?->doctor_name_snapshot ?: $item->prescription?->doctor?->name ?: 'No doctor' }}</p>
                                </td>
                                <td class="min-w-56 px-4 py-3 align-top">
                                    <p class="font-semibold text-ink-950">{{ $item->medicine_name_snapshot }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        Every {{ $item->refill_interval_days }} day{{ $item->refill_interval_days === 1 ? '' : 's' }}
                                        @if (($item->refill_reminder_days ?? 0) > 0)
                                            / remind {{ $item->refill_reminder_days }} day{{ $item->refill_reminder_days === 1 ? '' : 's' }} before
                                        @endif
                                    </p>
                                </td>
                                <td class="px-4 py-3 align-top font-semibold text-ink-900">{{ $item->remainingQuantity() }}</td>
                                <td class="px-4 py-3 align-top text-slate-700">{{ $item->last_dispensed_on?->format('d M Y') ?: 'Not dispensed' }}</td>
                                <td class="px-4 py-3 align-top text-slate-700">{{ $item->next_refill_due_on?->format('d M Y') ?: 'Not scheduled yet' }}</td>
                                <td class="px-4 py-3 align-top">
                                    <span class="badge {{ $badgeClass }}">{{ $item->refillStatusLabel() }}</span>
                                </td>
                                <td class="px-4 py-3 text-right align-top">
                                    <a href="{{ route('prescription-refills.show', $item) }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-12 text-center text-sm font-medium text-slate-500">No refill-tracked prescription lines found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-shell>
