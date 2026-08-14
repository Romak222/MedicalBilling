<x-app-shell page-title="Settings" section-label="Configuration">
    <x-slot:actions>
        <a href="{{ route('status') }}" class="btn-secondary">Back to Dashboard</a>
        <button type="submit" form="settings-form" class="btn-primary">Save Settings</button>
    </x-slot>

    <form id="settings-form" wire:submit="save" class="space-y-5">
        @if (session('status'))
            <div class="surface-panel border-medical-100 bg-medical-50 px-4 py-3 text-sm font-semibold text-medical-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid gap-5 xl:grid-cols-2">
            <section class="surface-panel p-5">
                <div class="border-b border-slate-200 pb-4">
                    <p class="section-kicker">Store profile</p>
                    <h2 class="mt-1 text-lg font-semibold text-ink-950">Business identity</h2>
                    <p class="mt-1 text-sm text-slate-600">These details appear on operational records and printed receipts.</p>
                </div>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <x-settings-field label="Store code" model="store.code" required />
                    <x-settings-field label="Store name" model="store.name" required />
                    <x-settings-field label="Legal name" model="store.legal_name" />
                    <x-settings-field label="GSTIN" model="store.gstin" />
                    <x-settings-field label="PAN" model="store.pan" />
                    <x-settings-field label="Drug licence number" model="store.drug_license_number" />
                    <x-settings-field label="Licence valid until" model="store.drug_license_valid_until" type="date" />
                    <x-settings-field label="Phone" model="store.phone" />
                    <x-settings-field label="Email" model="store.email" type="email" />
                    <x-settings-field label="City" model="store.city" />
                    <x-settings-field label="State" model="store.state" />
                    <x-settings-field label="Postal code" model="store.postal_code" />
                    <x-settings-field label="Address line 1" model="store.address_line_1" class="sm:col-span-2" />
                    <x-settings-field label="Address line 2" model="store.address_line_2" class="sm:col-span-2" />
                </div>
            </section>

            <section class="surface-panel p-5">
                <div class="border-b border-slate-200 pb-4">
                    <p class="section-kicker">Clinical responsibility</p>
                    <h2 class="mt-1 text-lg font-semibold text-ink-950">Primary registered pharmacist</h2>
                    <p class="mt-1 text-sm text-slate-600">Keep the responsible pharmacist record current for dispensing and controlled-medicine workflows.</p>
                </div>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <x-settings-field label="Name" model="pharmacist.name" required />
                    <x-settings-field label="Registration number" model="pharmacist.registration_number" />
                    <x-settings-field label="Council name" model="pharmacist.council_name" />
                    <x-settings-field label="Licence valid until" model="pharmacist.license_valid_until" type="date" />
                    <x-settings-field label="Phone" model="pharmacist.phone" />
                    <x-settings-field label="Email" model="pharmacist.email" type="email" />
                </div>
            </section>

            <section class="surface-panel p-5">
                <div class="border-b border-slate-200 pb-4">
                    <p class="section-kicker">Billing controls</p>
                    <h2 class="mt-1 text-lg font-semibold text-ink-950">Invoice numbering</h2>
                    <p class="mt-1 text-sm text-slate-600">These values remain local and are applied to future billing workflows.</p>
                </div>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <x-settings-field label="Invoice prefix" model="billing.invoice_prefix" required />
                    <x-settings-field label="Financial year starts on" model="billing.financial_year_starts_on" type="date" required />
                </div>
            </section>

            <section class="surface-panel p-5">
                <div class="border-b border-slate-200 pb-4">
                    <p class="section-kicker">Receipt hardware</p>
                    <h2 class="mt-1 text-lg font-semibold text-ink-950">Printer configuration</h2>
                    <p class="mt-1 text-sm text-slate-600">Use the exact Windows printer names configured on this workstation.</p>
                </div>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <x-settings-field label="Default printer name" model="operations.default_printer_name" />
                    <x-settings-field label="Receipt printer name" model="operations.receipt_printer_name" />
                    <div>
                        <label for="receipt-paper-width" class="field-label">Receipt paper width</label>
                        <select id="receipt-paper-width" wire:model="printing.receipt_paper_width_mm" class="field-control mt-1">
                            <option value="58">58 mm</option>
                            <option value="80">80 mm</option>
                        </select>
                        @error('printing.receipt_paper_width_mm') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <x-settings-field label="Receipt copies" model="printing.receipt_copies" type="number" min="1" max="3" required />
                    <div class="sm:col-span-2">
                        <label for="receipt-footer" class="field-label">Receipt footer</label>
                        <textarea id="receipt-footer" wire:model="printing.receipt_footer" rows="3" class="field-control mt-1"></textarea>
                        @error('printing.receipt_footer') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </section>

            <section class="surface-panel p-5 xl:col-span-2">
                <div class="border-b border-slate-200 pb-4">
                    <p class="section-kicker">Local resilience</p>
                    <h2 class="mt-1 text-lg font-semibold text-ink-950">Backup location</h2>
                    <p class="mt-1 text-sm text-slate-600">Use a local or removable drive path that is available to the desktop application.</p>
                </div>

                <div class="mt-5 max-w-2xl">
                    <x-settings-field label="Default backup path" model="operations.backup_path" required />
                </div>
            </section>
        </div>

        <div class="flex justify-end border-t border-slate-200 pt-5">
            <button type="submit" class="btn-primary">Save Settings</button>
        </div>
    </form>
</x-app-shell>
