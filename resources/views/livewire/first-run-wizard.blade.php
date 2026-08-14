<div class="app-background min-h-screen p-3 sm:p-4 lg:p-6">
    <div class="mx-auto min-h-[calc(100vh-2rem)] max-w-7xl overflow-hidden rounded-lg border border-white/70 bg-[#f4f6fb] shadow-2xl shadow-slate-900/10">
    <header class="clinical-header border-b border-slate-200/70 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-4">
            <div>
                <p class="section-kicker">{{ config('pharmacy.store_code') }}</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-normal text-ink-950">First-run Setup</h1>
            </div>
            <div class="text-right">
                <p class="text-sm font-semibold text-ink-700">Phase 3</p>
                <p class="text-xs text-slate-500">Installation profile</p>
            </div>
        </div>
    </header>

    <main class="mx-auto grid max-w-7xl gap-6 px-5 py-6 lg:grid-cols-[280px_1fr]">
        <aside class="surface-panel h-fit p-3">
            <nav class="space-y-1 text-sm font-medium">
                @foreach ([
                    1 => 'Store',
                    2 => 'Licences',
                    3 => 'Operations',
                    4 => 'Owner',
                    5 => 'Review',
                ] as $number => $label)
                    <button
                        type="button"
                        wire:click="$set('step', {{ $number }})"
                        class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-left transition {{ $step === $number ? 'bg-medical-700 text-white shadow-sm' : 'text-slate-600 hover:bg-medical-50 hover:text-ink-900' }}"
                    >
                        <span>{{ $label }}</span>
                        <span class="flex h-6 w-6 items-center justify-center rounded-full border text-xs {{ $step === $number ? 'border-medical-500 bg-medical-500 text-ink-950' : 'border-slate-300 bg-white text-slate-500' }}">
                            {{ $number }}
                        </span>
                    </button>
                @endforeach
            </nav>
        </aside>

        <form wire:submit="complete" class="space-y-6">
            @if ($errors->has('setup'))
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 shadow-sm">
                    {{ $errors->first('setup') }}
                </div>
            @endif

            @if ($step === 1)
                <section class="surface-panel p-5">
                    <h2 class="text-lg font-semibold tracking-normal text-ink-950">Store Profile</h2>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-medium text-ink-700">Store Code</span>
                            <input wire:model="store.code" type="text" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-medical-600 focus:outline-none focus:ring-2 focus:ring-medical-100">
                            @error('store.code') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-ink-700">Store Name</span>
                            <input wire:model="store.name" type="text" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-medical-600 focus:outline-none focus:ring-2 focus:ring-medical-100">
                            @error('store.name') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>

                        <label class="block md:col-span-2">
                            <span class="text-sm font-medium text-ink-700">Legal Name</span>
                            <input wire:model="store.legal_name" type="text" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-medical-600 focus:outline-none focus:ring-2 focus:ring-medical-100">
                            @error('store.legal_name') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>

                        <label class="block md:col-span-2">
                            <span class="text-sm font-medium text-ink-700">Address Line 1</span>
                            <input wire:model="store.address_line_1" type="text" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-medical-600 focus:outline-none focus:ring-2 focus:ring-medical-100">
                            @error('store.address_line_1') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>

                        <label class="block md:col-span-2">
                            <span class="text-sm font-medium text-ink-700">Address Line 2</span>
                            <input wire:model="store.address_line_2" type="text" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-medical-600 focus:outline-none focus:ring-2 focus:ring-medical-100">
                            @error('store.address_line_2') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-ink-700">City</span>
                            <input wire:model="store.city" type="text" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-medical-600 focus:outline-none focus:ring-2 focus:ring-medical-100">
                            @error('store.city') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-ink-700">State</span>
                            <input wire:model="store.state" type="text" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-medical-600 focus:outline-none focus:ring-2 focus:ring-medical-100">
                            @error('store.state') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-ink-700">Postal Code</span>
                            <input wire:model="store.postal_code" type="text" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-medical-600 focus:outline-none focus:ring-2 focus:ring-medical-100">
                            @error('store.postal_code') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-ink-700">Phone</span>
                            <input wire:model="store.phone" type="text" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-medical-600 focus:outline-none focus:ring-2 focus:ring-medical-100">
                            @error('store.phone') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>

                        <label class="block md:col-span-2">
                            <span class="text-sm font-medium text-ink-700">Email</span>
                            <input wire:model="store.email" type="email" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-medical-600 focus:outline-none focus:ring-2 focus:ring-medical-100">
                            @error('store.email') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>
                    </div>
                </section>
            @endif

            @if ($step === 2)
                <section class="surface-panel p-5">
                    <h2 class="text-lg font-semibold tracking-normal text-ink-950">Licences and Pharmacist</h2>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-medium text-ink-700">GSTIN</span>
                            <input wire:model="store.gstin" type="text" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm uppercase focus:border-medical-600 focus:outline-none focus:ring-2 focus:ring-medical-100">
                            @error('store.gstin') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-ink-700">PAN</span>
                            <input wire:model="store.pan" type="text" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm uppercase focus:border-medical-600 focus:outline-none focus:ring-2 focus:ring-medical-100">
                            @error('store.pan') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-ink-700">Drug Licence Number</span>
                            <input wire:model="store.drug_license_number" type="text" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-medical-600 focus:outline-none focus:ring-2 focus:ring-medical-100">
                            @error('store.drug_license_number') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-ink-700">Drug Licence Valid Until</span>
                            <input wire:model="store.drug_license_valid_until" type="date" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-medical-600 focus:outline-none focus:ring-2 focus:ring-medical-100">
                            @error('store.drug_license_valid_until') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-ink-700">Registered Pharmacist</span>
                            <input wire:model="pharmacist.name" type="text" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-medical-600 focus:outline-none focus:ring-2 focus:ring-medical-100">
                            @error('pharmacist.name') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-ink-700">Registration Number</span>
                            <input wire:model="pharmacist.registration_number" type="text" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-medical-600 focus:outline-none focus:ring-2 focus:ring-medical-100">
                            @error('pharmacist.registration_number') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-ink-700">Council Name</span>
                            <input wire:model="pharmacist.council_name" type="text" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-medical-600 focus:outline-none focus:ring-2 focus:ring-medical-100">
                            @error('pharmacist.council_name') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-ink-700">Pharmacist Licence Valid Until</span>
                            <input wire:model="pharmacist.license_valid_until" type="date" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-medical-600 focus:outline-none focus:ring-2 focus:ring-medical-100">
                            @error('pharmacist.license_valid_until') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-ink-700">Pharmacist Phone</span>
                            <input wire:model="pharmacist.phone" type="text" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-medical-600 focus:outline-none focus:ring-2 focus:ring-medical-100">
                            @error('pharmacist.phone') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-ink-700">Pharmacist Email</span>
                            <input wire:model="pharmacist.email" type="email" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-medical-600 focus:outline-none focus:ring-2 focus:ring-medical-100">
                            @error('pharmacist.email') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>
                    </div>
                </section>
            @endif

            @if ($step === 3)
                <section class="surface-panel p-5">
                    <h2 class="text-lg font-semibold tracking-normal text-ink-950">Operations</h2>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-medium text-ink-700">Invoice Prefix</span>
                            <input wire:model="billing.invoice_prefix" type="text" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm uppercase focus:border-medical-600 focus:outline-none focus:ring-2 focus:ring-medical-100">
                            @error('billing.invoice_prefix') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-ink-700">Financial Year Starts On</span>
                            <input wire:model="billing.financial_year_starts_on" type="date" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-medical-600 focus:outline-none focus:ring-2 focus:ring-medical-100">
                            @error('billing.financial_year_starts_on') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-ink-700">Default Printer</span>
                            <input wire:model="operations.default_printer_name" type="text" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-medical-600 focus:outline-none focus:ring-2 focus:ring-medical-100">
                            @error('operations.default_printer_name') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-ink-700">Receipt Printer</span>
                            <input wire:model="operations.receipt_printer_name" type="text" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-medical-600 focus:outline-none focus:ring-2 focus:ring-medical-100">
                            @error('operations.receipt_printer_name') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>

                        <label class="block md:col-span-2">
                            <span class="text-sm font-medium text-ink-700">Backup Path</span>
                            <input wire:model="operations.backup_path" type="text" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-medical-600 focus:outline-none focus:ring-2 focus:ring-medical-100">
                            @error('operations.backup_path') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>
                    </div>
                </section>
            @endif

            @if ($step === 4)
                <section class="surface-panel p-5">
                    <h2 class="text-lg font-semibold tracking-normal text-ink-950">Owner Account</h2>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <label class="block md:col-span-2">
                            <span class="text-sm font-medium text-ink-700">Owner Name</span>
                            <input wire:model="owner.name" type="text" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-medical-600 focus:outline-none focus:ring-2 focus:ring-medical-100">
                            @error('owner.name') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>

                        <label class="block md:col-span-2">
                            <span class="text-sm font-medium text-ink-700">Owner Email</span>
                            <input wire:model="owner.email" type="email" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-medical-600 focus:outline-none focus:ring-2 focus:ring-medical-100">
                            @error('owner.email') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-ink-700">Password</span>
                            <input wire:model="owner.password" type="password" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-medical-600 focus:outline-none focus:ring-2 focus:ring-medical-100">
                            @error('owner.password') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-ink-700">Confirm Password</span>
                            <input wire:model="owner.password_confirmation" type="password" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-medical-600 focus:outline-none focus:ring-2 focus:ring-medical-100">
                        </label>
                    </div>
                </section>
            @endif

            @if ($step === 5)
                <section class="surface-panel p-5">
                    <h2 class="text-lg font-semibold tracking-normal text-ink-950">Review</h2>

                    <dl class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="surface-subtle p-4">
                            <dt class="metric-label">Store</dt>
                            <dd class="mt-2 text-sm font-semibold text-ink-950">{{ $store['name'] ?: 'Not set' }}</dd>
                            <dd class="mt-1 text-sm text-slate-600">{{ $store['code'] ?: 'No code' }}</dd>
                        </div>

                        <div class="surface-subtle p-4">
                            <dt class="metric-label">Pharmacist</dt>
                            <dd class="mt-2 text-sm font-semibold text-ink-950">{{ $pharmacist['name'] ?: 'Not set' }}</dd>
                            <dd class="mt-1 text-sm text-slate-600">{{ $pharmacist['registration_number'] ?: 'No registration number' }}</dd>
                        </div>

                        <div class="surface-subtle p-4">
                            <dt class="metric-label">Billing</dt>
                            <dd class="mt-2 text-sm font-semibold text-ink-950">{{ $billing['invoice_prefix'] ?: 'No prefix' }}</dd>
                            <dd class="mt-1 text-sm text-slate-600">{{ $billing['financial_year_starts_on'] ?: 'No financial year date' }}</dd>
                        </div>

                        <div class="surface-subtle p-4">
                            <dt class="metric-label">Owner</dt>
                            <dd class="mt-2 text-sm font-semibold text-ink-950">{{ $owner['name'] ?: 'Not set' }}</dd>
                            <dd class="mt-1 text-sm text-slate-600">{{ $owner['email'] ?: 'No email' }}</dd>
                        </div>
                    </dl>
                </section>
            @endif

            <div class="flex items-center justify-between gap-3">
                <button
                    type="button"
                    wire:click="previousStep"
                    @disabled($step === 1)
                    class="btn-secondary"
                >
                    Back
                </button>

                @if ($step < 5)
                    <button
                        type="button"
                        wire:click="nextStep"
                        class="btn-primary"
                    >
                        Continue
                    </button>
                @else
                    <button
                        type="submit"
                        class="btn-accent"
                    >
                        Complete Setup
                    </button>
                @endif
            </div>
        </form>
    </main>
    </div>
</div>
