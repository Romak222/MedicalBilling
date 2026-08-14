<x-app-shell :page-title="$customerId ? 'Edit Customer' : 'Add New Customer'" section-label="Customer Directory">
    <x-slot:actions>
        <a href="{{ route('customers.index') }}" class="btn-secondary">Back to Customers</a>
        @if ($customerId)
            <a href="{{ route('customers.show', $customerId) }}" class="btn-secondary">View Customer</a>
        @endif
        <button type="button" wire:click="save" class="btn-primary">{{ $customerId ? 'Save Changes' : 'Create Customer' }}</button>
    </x-slot>

    <form wire:submit="save" class="space-y-5">
        <section class="surface-panel p-5">
            <div class="flex flex-col gap-1 border-b border-slate-200 pb-4">
                <p class="section-kicker">Profile</p>
                <h2 class="text-lg font-semibold text-ink-950">Customer Identity</h2>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-3">
                <label class="block lg:col-span-2">
                    <span class="text-sm font-medium text-ink-700">Customer Name</span>
                    <input wire:model="customer.name" type="text" class="field-control mt-1">
                    @error('customer.name') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Customer Code</span>
                    <input wire:model="customer.code" type="text" class="field-control mt-1 uppercase">
                    @error('customer.code') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Phone</span>
                    <input wire:model="customer.phone" type="text" class="field-control mt-1">
                    @error('customer.phone') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Email</span>
                    <input wire:model="customer.email" type="email" class="field-control mt-1">
                    @error('customer.email') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">GSTIN</span>
                    <input wire:model="customer.gstin" type="text" class="field-control mt-1 uppercase">
                    @error('customer.gstin') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>
            </div>
        </section>

        <section class="surface-panel p-5">
            <div class="flex flex-col gap-1 border-b border-slate-200 pb-4">
                <p class="section-kicker">Address</p>
                <h2 class="text-lg font-semibold text-ink-950">Location and Notes</h2>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-3">
                <label class="block lg:col-span-2">
                    <span class="text-sm font-medium text-ink-700">Address Line 1</span>
                    <input wire:model="customer.address_line_1" type="text" class="field-control mt-1">
                    @error('customer.address_line_1') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Address Line 2</span>
                    <input wire:model="customer.address_line_2" type="text" class="field-control mt-1">
                    @error('customer.address_line_2') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">City</span>
                    <input wire:model="customer.city" type="text" class="field-control mt-1">
                    @error('customer.city') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">State</span>
                    <input wire:model="customer.state" type="text" class="field-control mt-1">
                    @error('customer.state') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Postal Code</span>
                    <input wire:model="customer.postal_code" type="text" class="field-control mt-1">
                    @error('customer.postal_code') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block lg:col-span-3">
                    <span class="text-sm font-medium text-ink-700">Notes</span>
                    <textarea wire:model="customer.notes" rows="3" class="field-control mt-1"></textarea>
                    @error('customer.notes') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>
            </div>
        </section>

        <section class="surface-panel p-5">
            <div class="flex flex-col gap-1 border-b border-slate-200 pb-4">
                <p class="section-kicker">Account</p>
                <h2 class="text-lg font-semibold text-ink-950">Balance, Loyalty and Consent</h2>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-4">
                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Opening Balance</span>
                    <input wire:model="customer.opening_balance" type="text" inputmode="decimal" class="field-control mt-1">
                    @error('customer.opening_balance') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Credit Limit</span>
                    <input wire:model="customer.credit_limit" type="text" inputmode="decimal" class="field-control mt-1">
                    @error('customer.credit_limit') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Outstanding Balance</span>
                    <input wire:model="customer.outstanding_balance" type="text" inputmode="decimal" class="field-control mt-1">
                    @error('customer.outstanding_balance') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Loyalty Points</span>
                    <input wire:model="customer.loyalty_points" type="number" min="0" class="field-control mt-1">
                    @error('customer.loyalty_points') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-3">
                <label class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-ink-700">
                    <input wire:model="customer.reminder_consent" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-medical-600 focus:ring-medical-500">
                    Reminder Consent
                </label>
                <label class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-ink-700">
                    <input wire:model="customer.whatsapp_consent" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-medical-600 focus:ring-medical-500">
                    WhatsApp Consent
                </label>
                <label class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-ink-700">
                    <input wire:model="customer.sms_consent" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-medical-600 focus:ring-medical-500">
                    SMS Consent
                </label>
            </div>
        </section>

        <div class="sticky bottom-0 flex items-center justify-end gap-3 rounded-lg border border-white/80 bg-white/95 p-4 shadow-lg shadow-slate-900/10 backdrop-blur">
            <a href="{{ route('customers.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $customerId ? 'Save Changes' : 'Create Customer' }}</button>
        </div>
    </form>
</x-app-shell>
