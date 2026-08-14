<x-app-shell :page-title="$supplierId ? 'Edit Supplier' : 'Add New Supplier'" section-label="Supplier Directory">
    <x-slot:actions>
        <a href="{{ route('suppliers.index') }}" class="btn-secondary">Back to Suppliers</a>
        @if ($supplierId)
            <a href="{{ route('suppliers.show', $supplierId) }}" class="btn-secondary">View Supplier</a>
        @endif
        <button type="button" wire:click="save" class="btn-primary">{{ $supplierId ? 'Save Changes' : 'Create Supplier' }}</button>
    </x-slot>

    <form wire:submit="save" class="space-y-5">
        <section class="surface-panel p-5">
            <div class="flex flex-col gap-1 border-b border-slate-200 pb-4">
                <p class="section-kicker">Profile</p>
                <h2 class="text-lg font-semibold text-ink-950">Supplier Identity</h2>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-3">
                <label class="block lg:col-span-2">
                    <span class="text-sm font-medium text-ink-700">Supplier Name</span>
                    <input wire:model="supplier.name" type="text" class="field-control mt-1">
                    @error('supplier.name') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Supplier Code</span>
                    <input wire:model="supplier.code" type="text" class="field-control mt-1 uppercase">
                    @error('supplier.code') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Phone</span>
                    <input wire:model="supplier.phone" type="text" class="field-control mt-1">
                    @error('supplier.phone') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Email</span>
                    <input wire:model="supplier.email" type="email" class="field-control mt-1">
                    @error('supplier.email') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">GSTIN</span>
                    <input wire:model="supplier.gstin" type="text" class="field-control mt-1 uppercase">
                    @error('supplier.gstin') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>
            </div>
        </section>

        <section class="surface-panel p-5">
            <div class="flex flex-col gap-1 border-b border-slate-200 pb-4">
                <p class="section-kicker">Legal and Address</p>
                <h2 class="text-lg font-semibold text-ink-950">Licence, Location and Notes</h2>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-3">
                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Drug Licence Number</span>
                    <input wire:model="supplier.drug_license_number" type="text" class="field-control mt-1">
                    @error('supplier.drug_license_number') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Licence Valid Until</span>
                    <input wire:model="supplier.drug_license_valid_until" type="date" class="field-control mt-1">
                    @error('supplier.drug_license_valid_until') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Postal Code</span>
                    <input wire:model="supplier.postal_code" type="text" class="field-control mt-1">
                    @error('supplier.postal_code') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block lg:col-span-2">
                    <span class="text-sm font-medium text-ink-700">Address Line 1</span>
                    <input wire:model="supplier.address_line_1" type="text" class="field-control mt-1">
                    @error('supplier.address_line_1') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Address Line 2</span>
                    <input wire:model="supplier.address_line_2" type="text" class="field-control mt-1">
                    @error('supplier.address_line_2') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">City</span>
                    <input wire:model="supplier.city" type="text" class="field-control mt-1">
                    @error('supplier.city') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">State</span>
                    <input wire:model="supplier.state" type="text" class="field-control mt-1">
                    @error('supplier.state') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block lg:col-span-3">
                    <span class="text-sm font-medium text-ink-700">Notes</span>
                    <textarea wire:model="supplier.notes" rows="3" class="field-control mt-1"></textarea>
                    @error('supplier.notes') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>
            </div>
        </section>

        <section class="surface-panel p-5">
            <div class="flex flex-col gap-1 border-b border-slate-200 pb-4">
                <p class="section-kicker">Terms</p>
                <h2 class="text-lg font-semibold text-ink-950">Credit Terms and Balances</h2>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-4">
                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Payment Terms Days</span>
                    <input wire:model="supplier.payment_terms_days" type="number" min="0" class="field-control mt-1">
                    @error('supplier.payment_terms_days') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Opening Balance</span>
                    <input wire:model="supplier.opening_balance" type="text" inputmode="decimal" class="field-control mt-1">
                    @error('supplier.opening_balance') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Credit Limit</span>
                    <input wire:model="supplier.credit_limit" type="text" inputmode="decimal" class="field-control mt-1">
                    @error('supplier.credit_limit') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Outstanding Balance</span>
                    <input wire:model="supplier.outstanding_balance" type="text" inputmode="decimal" class="field-control mt-1">
                    @error('supplier.outstanding_balance') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>
            </div>
        </section>

        <section class="surface-panel p-5">
            <div class="flex flex-col gap-1 border-b border-slate-200 pb-4">
                <p class="section-kicker">Primary Contact</p>
                <h2 class="text-lg font-semibold text-ink-950">Supplier Contact Person</h2>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-4">
                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Contact Name</span>
                    <input wire:model="contact.name" type="text" class="field-control mt-1">
                    @error('contact.name') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Role</span>
                    <input wire:model="contact.role" type="text" class="field-control mt-1">
                    @error('contact.role') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Contact Phone</span>
                    <input wire:model="contact.phone" type="text" class="field-control mt-1">
                    @error('contact.phone') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Contact Email</span>
                    <input wire:model="contact.email" type="email" class="field-control mt-1">
                    @error('contact.email') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>
            </div>
        </section>

        <div class="sticky bottom-0 flex items-center justify-end gap-3 rounded-lg border border-white/80 bg-white/95 p-4 shadow-lg shadow-slate-900/10 backdrop-blur">
            <a href="{{ route('suppliers.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $supplierId ? 'Save Changes' : 'Create Supplier' }}</button>
        </div>
    </form>
</x-app-shell>
