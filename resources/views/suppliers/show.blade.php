<x-layouts.app :title="config('app.name').' Supplier Detail'">
    <x-app-shell :page-title="$supplier->name" section-label="Supplier Detail">
        <x-slot:actions>
            <a href="{{ route('suppliers.index') }}" class="btn-secondary">Back to Suppliers</a>
            @if (auth()->user()?->hasPermission('accounting.view'))
                <a href="{{ route('suppliers.ledger', $supplier) }}" class="btn-secondary">Ledger</a>
                <a href="{{ route('supplier-payments.index', $supplier) }}" class="btn-secondary">Payments</a>
            @endif
            @if (auth()->user()?->hasPermission('suppliers.manage'))
                <a href="{{ route('suppliers.edit', $supplier) }}" class="btn-primary">Edit Supplier</a>
            @endif
        </x-slot>

        <div class="space-y-5">
            @if (session('status'))
                <div class="surface-panel border-medical-100 bg-medical-50 px-4 py-3 text-sm font-semibold text-medical-800">
                    {{ session('status') }}
                </div>
            @endif

            <section class="surface-panel p-5">
                <div class="flex flex-col gap-3 border-b border-slate-200 pb-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="section-kicker">Read Only</p>
                        <h2 class="mt-1 text-xl font-semibold text-ink-950">{{ $supplier->name }}</h2>
                        <p class="mt-2 text-sm text-slate-600">{{ $supplier->code ?: 'No supplier code' }}</p>
                    </div>
                    <span class="badge {{ $supplier->is_active ? 'bg-medical-50 text-medical-700' : 'bg-slate-200 text-slate-600' }}">
                        {{ $supplier->is_active ? 'Active' : 'Deleted' }}
                    </span>
                </div>

                <dl class="mt-5 grid gap-4 lg:grid-cols-3">
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">GSTIN</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $supplier->gstin ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Drug Licence</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $supplier->drug_license_number ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Licence Valid Until</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $supplier->drug_license_valid_until?->format('d M Y') ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Phone</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $supplier->phone ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Email</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $supplier->email ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Payment Terms</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $supplier->payment_terms_days === null ? 'Not set' : $supplier->payment_terms_days.' days' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Opening Balance</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ number_format((float) $supplier->opening_balance, 2) }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Credit Limit</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $supplier->credit_limit === null ? 'Not set' : number_format((float) $supplier->credit_limit, 2) }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Outstanding Balance</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ number_format((float) $supplier->outstanding_balance, 2) }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4 lg:col-span-2">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Address</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">
                            {{ $supplier->address_line_1 ?: 'Not set' }}
                            @if ($supplier->address_line_2)
                                , {{ $supplier->address_line_2 }}
                            @endif
                            @if ($supplier->city || $supplier->state || $supplier->postal_code)
                                <span class="block pt-1 text-slate-700">{{ $supplier->city }}{{ $supplier->state ? ', '.$supplier->state : '' }} {{ $supplier->postal_code }}</span>
                            @endif
                        </dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Primary Contact</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">
                            {{ $supplier->primaryContact?->name ?: 'Not set' }}
                            @if ($supplier->primaryContact?->role)
                                <span class="block pt-1 text-slate-700">{{ $supplier->primaryContact->role }}</span>
                            @endif
                            @if ($supplier->primaryContact?->phone || $supplier->primaryContact?->email)
                                <span class="block pt-1 text-slate-700">{{ $supplier->primaryContact?->phone }} {{ $supplier->primaryContact?->email }}</span>
                            @endif
                        </dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4 lg:col-span-3">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Notes</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $supplier->notes ?: 'Not set' }}</dd>
                    </div>
                </dl>
            </section>
        </div>
    </x-app-shell>
</x-layouts.app>
