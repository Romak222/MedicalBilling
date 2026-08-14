<x-app-shell :page-title="$doctorId ? 'Edit Doctor' : 'Add New Doctor'" section-label="Doctor Directory">
    <x-slot:actions>
        <a href="{{ route('doctors.index') }}" class="btn-secondary">Back to Doctors</a>
        @if ($doctorId)
            <a href="{{ route('doctors.show', $doctorId) }}" class="btn-secondary">View Doctor</a>
        @endif
        <button type="button" wire:click="save" class="btn-primary">{{ $doctorId ? 'Save Changes' : 'Create Doctor' }}</button>
    </x-slot>

    <form wire:submit="save" class="space-y-5">
        <section class="surface-panel p-5">
            <div class="flex flex-col gap-1 border-b border-slate-200 pb-4">
                <p class="section-kicker">Identity</p>
                <h2 class="text-lg font-semibold text-ink-950">Doctor Profile</h2>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-3">
                <label class="block lg:col-span-2">
                    <span class="text-sm font-medium text-ink-700">Doctor Name</span>
                    <input wire:model="doctor.name" type="text" class="field-control mt-1">
                    @error('doctor.name') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Registration Number</span>
                    <input wire:model="doctor.registration_number" type="text" class="field-control mt-1 uppercase">
                    @error('doctor.registration_number') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Specialization</span>
                    <input wire:model="doctor.specialization" type="text" class="field-control mt-1">
                    @error('doctor.specialization') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block lg:col-span-2">
                    <span class="text-sm font-medium text-ink-700">Clinic or Hospital</span>
                    <input wire:model="doctor.clinic_name" type="text" class="field-control mt-1">
                    @error('doctor.clinic_name') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>
            </div>
        </section>

        <section class="surface-panel p-5">
            <div class="flex flex-col gap-1 border-b border-slate-200 pb-4">
                <p class="section-kicker">Contact</p>
                <h2 class="text-lg font-semibold text-ink-950">Phone and Email</h2>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-3">
                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Phone</span>
                    <input wire:model="doctor.phone" type="text" class="field-control mt-1">
                    @error('doctor.phone') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Alternate Phone</span>
                    <input wire:model="doctor.alternate_phone" type="text" class="field-control mt-1">
                    @error('doctor.alternate_phone') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Email</span>
                    <input wire:model="doctor.email" type="email" class="field-control mt-1">
                    @error('doctor.email') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>
            </div>
        </section>

        <section class="surface-panel p-5">
            <div class="flex flex-col gap-1 border-b border-slate-200 pb-4">
                <p class="section-kicker">Address</p>
                <h2 class="text-lg font-semibold text-ink-950">Clinic Location</h2>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-3">
                <label class="block lg:col-span-2">
                    <span class="text-sm font-medium text-ink-700">Address Line 1</span>
                    <input wire:model="doctor.address_line_1" type="text" class="field-control mt-1">
                    @error('doctor.address_line_1') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Address Line 2</span>
                    <input wire:model="doctor.address_line_2" type="text" class="field-control mt-1">
                    @error('doctor.address_line_2') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">City</span>
                    <input wire:model="doctor.city" type="text" class="field-control mt-1">
                    @error('doctor.city') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">State</span>
                    <input wire:model="doctor.state" type="text" class="field-control mt-1">
                    @error('doctor.state') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Postal Code</span>
                    <input wire:model="doctor.postal_code" type="text" class="field-control mt-1">
                    @error('doctor.postal_code') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>
            </div>
        </section>

        <section class="surface-panel p-5">
            <div class="flex flex-col gap-1 border-b border-slate-200 pb-4">
                <p class="section-kicker">Notes</p>
                <h2 class="text-lg font-semibold text-ink-950">Internal Notes</h2>
            </div>

            <label class="mt-5 block">
                <span class="text-sm font-medium text-ink-700">Notes</span>
                <textarea wire:model="doctor.notes" rows="4" class="field-control mt-1"></textarea>
                @error('doctor.notes') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
            </label>
        </section>

        <div class="sticky bottom-0 flex items-center justify-end gap-3 rounded-lg border border-white/80 bg-white/95 p-4 shadow-lg shadow-slate-900/10 backdrop-blur">
            <a href="{{ route('doctors.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $doctorId ? 'Save Changes' : 'Create Doctor' }}</button>
        </div>
    </form>
</x-app-shell>
