<x-app-shell :page-title="$prescriptionId ? 'Edit Prescription' : 'Add New Prescription'" section-label="Prescription Register">
    <x-slot:actions>
        <a href="{{ route('prescriptions.index') }}" class="btn-secondary">Back to Prescriptions</a>
        @if ($prescriptionId)
            <a href="{{ route('prescriptions.show', $prescriptionId) }}" class="btn-secondary">View Prescription</a>
        @endif
        @if (! $readOnly)
            <button type="button" wire:click="save" class="btn-primary">{{ $prescriptionId ? 'Save Changes' : 'Create Prescription' }}</button>
        @endif
    </x-slot>

    <form wire:submit="save" class="space-y-5">
        @if ($readOnly)
            <div class="surface-panel border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
                This prescription already has dispensed quantities, so the record is now read-only.
            </div>
        @endif

        <section class="surface-panel p-5">
            <div class="flex flex-col gap-1 border-b border-slate-200 pb-4">
                <p class="section-kicker">Header</p>
                <h2 class="text-lg font-semibold text-ink-950">Patient, Doctor and Attachment</h2>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-4">
                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Prescription Number</span>
                    <input wire:model="prescription.prescription_number" type="text" class="field-control mt-1 uppercase" placeholder="Auto if blank" @disabled($readOnly)>
                    @error('prescription.prescription_number') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Prescription Date</span>
                    <input wire:model="prescription.prescription_date" type="date" class="field-control mt-1" @disabled($readOnly)>
                    @error('prescription.prescription_date') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Valid Until</span>
                    <input wire:model="prescription.valid_until" type="date" class="field-control mt-1" @disabled($readOnly)>
                    @error('prescription.valid_until') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Patient Record</span>
                    <select wire:model="prescription.patient_id" class="field-control mt-1" @disabled($readOnly)>
                        <option value="">Choose patient</option>
                        @foreach ($patients as $patient)
                            <option value="{{ $patient->id }}">{{ $patient->full_name }}{{ $patient->patient_code ? ' / '.$patient->patient_code : '' }}{{ $patient->customer ? ' / '.$patient->customer->name : '' }}</option>
                        @endforeach
                    </select>
                    @error('prescription.patient_id') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Doctor Record</span>
                    <select wire:model="prescription.doctor_id" class="field-control mt-1" @disabled($readOnly)>
                        <option value="">No linked doctor record</option>
                        @foreach ($doctors as $doctor)
                            <option value="{{ $doctor->id }}">{{ $doctor->name }}{{ $doctor->registration_number ? ' / '.$doctor->registration_number : '' }}</option>
                        @endforeach
                    </select>
                    @error('prescription.doctor_id') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block lg:col-span-2">
                    <span class="text-sm font-medium text-ink-700">Doctor Name Snapshot</span>
                    <input wire:model="prescription.doctor_name_snapshot" type="text" class="field-control mt-1" placeholder="Used when doctor is not linked as a record" @disabled($readOnly)>
                    @error('prescription.doctor_name_snapshot') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block lg:col-span-2">
                    <span class="text-sm font-medium text-ink-700">Prescription Attachment</span>
                    <input wire:model="attachment" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf" class="field-control mt-1" @disabled($readOnly)>
                    @error('attachment') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                    @if ($existingAttachmentName)
                        <div class="mt-2 flex flex-wrap items-center gap-3 text-xs font-medium text-slate-600">
                            <span>Current file: {{ $existingAttachmentName }}</span>
                            @if (! $readOnly)
                                <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-ink-700">
                                    <input wire:model="removeAttachment" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-medical-600 focus:ring-medical-500">
                                    Remove current file
                                </label>
                            @endif
                        </div>
                    @endif
                </label>
            </div>
        </section>

        <section class="surface-panel overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-slate-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="section-kicker">Lines</p>
                    <h2 class="mt-1 text-lg font-semibold text-ink-950">Prescribed Medicines</h2>
                </div>
                @if (! $readOnly)
                    <button type="button" wire:click="addItem" class="btn-secondary">Add Line</button>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="table-header">
                        <tr>
                            <th class="px-4 py-3">Product</th>
                            <th class="px-4 py-3">Medicine</th>
                            <th class="px-4 py-3">Dosage</th>
                            <th class="px-4 py-3">Qty</th>
                            <th class="px-4 py-3">Refill</th>
                            <th class="px-4 py-3">Notes</th>
                            @if (! $readOnly)
                                <th class="px-4 py-3 text-right">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($items as $index => $item)
                            <tr wire:key="prescription-item-{{ $index }}">
                                <td class="min-w-72 px-4 py-3 align-top">
                                    <select wire:model="items.{{ $index }}.product_id" wire:change="useProduct({{ $index }})" class="field-control" @disabled($readOnly)>
                                        <option value="">Manual medicine entry</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}">{{ $product->name }}{{ $product->sku ? ' / '.$product->sku : '' }}</option>
                                        @endforeach
                                    </select>
                                    @error("items.$index.product_id") <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                                </td>
                                <td class="min-w-72 px-4 py-3 align-top">
                                    <input wire:model="items.{{ $index }}.medicine_name_snapshot" type="text" class="field-control" @disabled($readOnly)>
                                    <input wire:model="items.{{ $index }}.unit_name_snapshot" type="text" class="field-control mt-2" placeholder="Unit" @disabled($readOnly)>
                                    @error("items.$index.medicine_name_snapshot") <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                                    @error("items.$index.unit_name_snapshot") <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                                </td>
                                <td class="min-w-56 px-4 py-3 align-top">
                                    <input wire:model="items.{{ $index }}.dosage_instructions" type="text" class="field-control" placeholder="1 tab twice daily" @disabled($readOnly)>
                                    @error("items.$index.dosage_instructions") <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                                </td>
                                <td class="min-w-32 px-4 py-3 align-top">
                                    <input wire:model="items.{{ $index }}.quantity_prescribed" type="text" inputmode="decimal" class="field-control" @disabled($readOnly)>
                                    @error("items.$index.quantity_prescribed") <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                                </td>
                                <td class="min-w-52 px-4 py-3 align-top">
                                    <input wire:model="items.{{ $index }}.refill_interval_days" type="number" min="1" max="365" class="field-control" placeholder="Every X days" @disabled($readOnly)>
                                    <input wire:model="items.{{ $index }}.refill_reminder_days" type="number" min="0" max="90" class="field-control mt-2" placeholder="Remind X days before" @disabled($readOnly)>
                                    @error("items.$index.refill_interval_days") <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                                    @error("items.$index.refill_reminder_days") <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                                </td>
                                <td class="min-w-64 px-4 py-3 align-top">
                                    <input wire:model="items.{{ $index }}.notes" type="text" class="field-control" @disabled($readOnly)>
                                    @error("items.$index.notes") <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                                </td>
                                @if (! $readOnly)
                                    <td class="px-4 py-3 text-right align-top">
                                        <button type="button" wire:click="removeItem({{ $index }})" class="rounded-md border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-700 shadow-sm transition hover:bg-red-50">
                                            Remove
                                        </button>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="surface-panel p-5">
            <div class="grid gap-4 lg:grid-cols-2">
                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Prescription Notes</span>
                    <textarea wire:model="prescription.notes" rows="4" class="field-control mt-1" @disabled($readOnly)></textarea>
                    @error('prescription.notes') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Pharmacist Notes</span>
                    <textarea wire:model="prescription.pharmacist_notes" rows="4" class="field-control mt-1" @disabled($readOnly)></textarea>
                    @error('prescription.pharmacist_notes') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>
            </div>
        </section>

        @if (! $readOnly)
            <div class="sticky bottom-0 flex items-center justify-end gap-3 rounded-lg border border-white/80 bg-white/95 p-4 shadow-lg shadow-slate-900/10 backdrop-blur">
                <a href="{{ route('prescriptions.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">{{ $prescriptionId ? 'Save Changes' : 'Create Prescription' }}</button>
            </div>
        @endif
    </form>
</x-app-shell>
