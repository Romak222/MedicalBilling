<x-app-shell :page-title="$patientId ? 'Edit Patient' : 'Add New Patient'" section-label="Patient Directory">
    <x-slot:actions>
        <a href="{{ route('patients.index') }}" class="btn-secondary">Back to Patients</a>
        @if ($patientId)
            <a href="{{ route('patients.show', $patientId) }}" class="btn-secondary">View Patient</a>
        @endif
        <button type="button" wire:click="save" class="btn-primary">{{ $patientId ? 'Save Changes' : 'Create Patient' }}</button>
    </x-slot>

    <form wire:submit="save" class="space-y-5">
        <section class="surface-panel p-5">
            <div class="flex flex-col gap-1 border-b border-slate-200 pb-4">
                <p class="section-kicker">Identity</p>
                <h2 class="text-lg font-semibold text-ink-950">Patient Profile</h2>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-3">
                <label class="block lg:col-span-2">
                    <span class="text-sm font-medium text-ink-700">Patient Name</span>
                    <input wire:model="patient.full_name" type="text" class="field-control mt-1">
                    @error('patient.full_name') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Patient Code</span>
                    <input wire:model="patient.patient_code" type="text" class="field-control mt-1 uppercase">
                    @error('patient.patient_code') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Linked Customer</span>
                    <select wire:model="patient.customer_id" class="field-control mt-1">
                        <option value="">No linked customer</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}{{ $customer->phone ? ' / '.$customer->phone : '' }}</option>
                        @endforeach
                    </select>
                    @error('patient.customer_id') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Primary Doctor Record</span>
                    <select wire:model="patient.primary_doctor_id" class="field-control mt-1">
                        <option value="">No linked doctor</option>
                        @foreach ($doctors as $doctor)
                            <option value="{{ $doctor->id }}">{{ $doctor->name }}{{ $doctor->registration_number ? ' / '.$doctor->registration_number : '' }}</option>
                        @endforeach
                    </select>
                    @error('patient.primary_doctor_id') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Phone</span>
                    <input wire:model="patient.phone" type="text" class="field-control mt-1">
                    @error('patient.phone') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Email</span>
                    <input wire:model="patient.email" type="email" class="field-control mt-1">
                    @error('patient.email') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Date of Birth</span>
                    <input wire:model="patient.date_of_birth" type="date" class="field-control mt-1">
                    @error('patient.date_of_birth') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Gender</span>
                    <select wire:model="patient.gender" class="field-control mt-1">
                        <option value="">Not set</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                        <option value="prefer_not_to_say">Prefer not to say</option>
                    </select>
                    @error('patient.gender') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block lg:col-span-2">
                    <span class="text-sm font-medium text-ink-700">Primary Doctor Name Snapshot</span>
                    <input wire:model="patient.primary_doctor_name" type="text" class="field-control mt-1">
                    @error('patient.primary_doctor_name') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>
            </div>
        </section>

        <section class="surface-panel p-5">
            <div class="flex flex-col gap-1 border-b border-slate-200 pb-4">
                <p class="section-kicker">Address</p>
                <h2 class="text-lg font-semibold text-ink-950">Location and Contact</h2>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-3">
                <label class="block lg:col-span-2">
                    <span class="text-sm font-medium text-ink-700">Address Line 1</span>
                    <input wire:model="patient.address_line_1" type="text" class="field-control mt-1">
                    @error('patient.address_line_1') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Address Line 2</span>
                    <input wire:model="patient.address_line_2" type="text" class="field-control mt-1">
                    @error('patient.address_line_2') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">City</span>
                    <input wire:model="patient.city" type="text" class="field-control mt-1">
                    @error('patient.city') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">State</span>
                    <input wire:model="patient.state" type="text" class="field-control mt-1">
                    @error('patient.state') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Postal Code</span>
                    <input wire:model="patient.postal_code" type="text" class="field-control mt-1">
                    @error('patient.postal_code') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>
            </div>
        </section>

        <section class="surface-panel p-5">
            <div class="flex flex-col gap-1 border-b border-slate-200 pb-4">
                <p class="section-kicker">Restricted</p>
                <h2 class="text-lg font-semibold text-ink-950">Allergies, Medical Notes and Consent</h2>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Allergies</span>
                    <textarea wire:model="patient.allergies" rows="4" class="field-control mt-1"></textarea>
                    @error('patient.allergies') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Medical Notes</span>
                    <textarea wire:model="patient.medical_notes" rows="4" class="field-control mt-1"></textarea>
                    @error('patient.medical_notes') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block lg:col-span-2">
                    <span class="text-sm font-medium text-ink-700">General Notes</span>
                    <textarea wire:model="patient.notes" rows="3" class="field-control mt-1"></textarea>
                    @error('patient.notes') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-3">
                <label class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-ink-700">
                    <input wire:model="patient.reminder_consent" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-medical-600 focus:ring-medical-500">
                    Reminder Consent
                </label>
                <label class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-ink-700">
                    <input wire:model="patient.whatsapp_consent" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-medical-600 focus:ring-medical-500">
                    WhatsApp Consent
                </label>
                <label class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-ink-700">
                    <input wire:model="patient.sms_consent" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-medical-600 focus:ring-medical-500">
                    SMS Consent
                </label>
            </div>
        </section>

        <div class="sticky bottom-0 flex items-center justify-end gap-3 rounded-lg border border-white/80 bg-white/95 p-4 shadow-lg shadow-slate-900/10 backdrop-blur">
            <a href="{{ route('patients.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $patientId ? 'Save Changes' : 'Create Patient' }}</button>
        </div>
    </form>
</x-app-shell>
