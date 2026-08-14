<x-app-shell page-title="Controlled Medicines" section-label="Compliance Register">
    <div class="space-y-5">
        <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <div class="metric-tile border-t-4 border-t-care-600">
                <p class="metric-label">Entries</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-care-700">{{ $stats['total'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-medical-600">
                <p class="metric-label">Dispensed</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-medical-700">{{ $stats['dispensed'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-alert-500">
                <p class="metric-label">Cancelled</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-alert-700">{{ $stats['cancelled'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-pharma-600">
                <p class="metric-label">Returned</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-pharma-700">{{ $stats['returned'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-slate-400">
                <p class="metric-label">Products</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-slate-600">{{ $stats['controlled_products'] }}</p>
            </div>
        </section>

        <section class="surface-panel overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-slate-200/80 bg-white p-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="inline-flex w-fit flex-wrap gap-1 rounded-lg bg-slate-100 p-1">
                    @foreach (['all' => 'All', 'sale' => 'Dispensed', 'sale_cancel' => 'Cancelled', 'sale_return' => 'Returned'] as $value => $label)
                        <button
                            type="button"
                            wire:click="$set('entryTypeFilter', '{{ $value }}')"
                            class="rounded-md px-3 py-2 text-sm font-semibold transition {{ $entryTypeFilter === $value ? 'bg-white text-medical-800 shadow-sm' : 'text-slate-600 hover:bg-white/70 hover:text-ink-900' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="Search product, patient, doctor, prescription, bill"
                    class="field-control xl:max-w-md"
                >
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="table-header">
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Product</th>
                            <th class="px-4 py-3">Patient / Doctor</th>
                            <th class="px-4 py-3">Prescription</th>
                            <th class="px-4 py-3">Source</th>
                            <th class="px-4 py-3">Entry</th>
                            <th class="px-4 py-3">Qty</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($entries as $entry)
                            <tr class="transition hover:bg-care-50/60">
                                <td class="px-4 py-3 align-top text-slate-700">{{ $entry->event_date?->format('d M Y') ?: 'Not set' }}</td>
                                <td class="min-w-60 px-4 py-3 align-top">
                                    <p class="font-semibold text-ink-950">{{ $entry->product_name_snapshot }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $entry->batch_number_snapshot ?: 'No batch' }}</p>
                                </td>
                                <td class="min-w-56 px-4 py-3 align-top text-slate-700">
                                    <p>{{ $entry->patient_name_snapshot ?: 'No patient' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $entry->doctor_name_snapshot ?: 'No doctor' }}</p>
                                </td>
                                <td class="px-4 py-3 align-top text-slate-700">{{ $entry->prescription_number_snapshot ?: 'Not linked' }}</td>
                                <td class="min-w-40 px-4 py-3 align-top text-slate-700">
                                    <p>{{ $entry->invoice_number_snapshot ?: 'No bill' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $entry->return_number_snapshot ?: 'No return' }}</p>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <span class="badge {{ $entry->isPositiveEffect() ? 'bg-medical-50 text-medical-700' : 'bg-alert-50 text-alert-700' }}">
                                        {{ $entry->entryTypeLabel() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-top font-semibold {{ $entry->isPositiveEffect() ? 'text-medical-800' : 'text-alert-700' }}">
                                    {{ $entry->quantity_effect }}
                                </td>
                                <td class="px-4 py-3 text-right align-top">
                                    <a href="{{ route('controlled-medicines.show', $entry) }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-12 text-center text-sm font-medium text-slate-500">No controlled-medicine register entries found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-shell>
