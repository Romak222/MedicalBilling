@props([
    'pageTitle' => config('app.name'),
    'sectionLabel' => null,
])

@php
    $canViewCatalogue = auth()->user()?->hasPermission('catalogue.view') ?? false;
    $canManageCatalogue = auth()->user()?->hasPermission('catalogue.manage') ?? false;
    $canViewSuppliers = auth()->user()?->hasPermission('suppliers.view') ?? false;
    $canViewCustomers = auth()->user()?->hasPermission('customers.view') ?? false;
    $canViewPatients = auth()->user()?->hasPermission('patients.view') ?? false;
    $canViewDoctors = auth()->user()?->hasPermission('doctors.view') ?? false;
    $canViewPrescriptions = auth()->user()?->hasPermission('prescriptions.view') ?? false;
    $canViewControlledMedicines = auth()->user()?->hasPermission('controlled_medicines.view') ?? false;
    $canViewPurchases = auth()->user()?->hasPermission('purchases.view') ?? false;
    $canViewInventory = auth()->user()?->hasPermission('inventory.view') ?? false;
    $canViewSales = auth()->user()?->hasPermission('sales.view') ?? false;
    $canViewCashDrawer = auth()->user()?->hasPermission('cash_drawer.view') ?? false;
    $canManageSettings = auth()->user()?->hasPermission('settings.manage') ?? false;
    $canViewReports = auth()->user()?->hasPermission('reports.view') ?? false;
    $canViewAccounting = auth()->user()?->hasPermission('accounting.view') ?? false;
    $canManageAccess = auth()->user()?->hasPermission('users.manage') || auth()->user()?->hasPermission('roles.manage');
    $navItems = [
        ['label' => 'Dashboard', 'mark' => 'D', 'route' => route('status'), 'active' => request()->routeIs('dashboard', 'status'), 'enabled' => true],
        ['label' => 'Products', 'mark' => 'P', 'route' => $canViewCatalogue ? route('products.index') : null, 'active' => request()->routeIs('products.index', 'products.show', 'products.create', 'products.edit', 'catalogue.index'), 'enabled' => $canViewCatalogue],
        ['label' => 'Product Options', 'mark' => 'O', 'route' => $canManageCatalogue ? route('catalogue.masters') : null, 'active' => request()->routeIs('catalogue.masters', 'catalogue.options.*'), 'enabled' => $canManageCatalogue],
        ['label' => 'Suppliers', 'mark' => 'S', 'route' => $canViewSuppliers ? route('suppliers.index') : null, 'active' => request()->routeIs('suppliers.*'), 'enabled' => $canViewSuppliers],
        ['label' => 'Customers', 'mark' => 'C', 'route' => $canViewCustomers ? route('customers.index') : null, 'active' => request()->routeIs('customers.*'), 'enabled' => $canViewCustomers],
        ['label' => 'Patients', 'mark' => 'T', 'route' => $canViewPatients ? route('patients.index') : null, 'active' => request()->routeIs('patients.*'), 'enabled' => $canViewPatients],
        ['label' => 'Doctors', 'mark' => 'H', 'route' => $canViewDoctors ? route('doctors.index') : null, 'active' => request()->routeIs('doctors.*'), 'enabled' => $canViewDoctors],
        ['label' => 'Prescriptions', 'mark' => 'X', 'route' => $canViewPrescriptions ? route('prescriptions.index') : null, 'active' => request()->routeIs('prescriptions.*'), 'enabled' => $canViewPrescriptions],
        ['label' => 'Refills', 'mark' => 'F', 'route' => $canViewPrescriptions ? route('prescription-refills.index') : null, 'active' => request()->routeIs('prescription-refills.*'), 'enabled' => $canViewPrescriptions],
        ['label' => 'Controlled', 'mark' => 'L', 'route' => $canViewControlledMedicines ? route('controlled-medicines.index') : null, 'active' => request()->routeIs('controlled-medicines.*'), 'enabled' => $canViewControlledMedicines],
        ['label' => 'Purchases', 'mark' => 'B', 'route' => $canViewPurchases ? route('purchase-invoices.index') : null, 'active' => request()->routeIs('purchases.*', 'purchase-orders.*', 'purchase-invoices.*', 'purchase-returns.*'), 'enabled' => $canViewPurchases],
        ['label' => 'Inventory', 'mark' => 'I', 'route' => $canViewInventory ? route('inventory.batches.index') : null, 'active' => request()->routeIs('inventory.*'), 'enabled' => $canViewInventory],
        ['label' => 'Billing', 'mark' => 'R', 'route' => $canViewSales ? route('sales-invoices.index') : null, 'active' => request()->routeIs('billing.*', 'sales-invoices.*'), 'enabled' => $canViewSales],
        ['label' => 'Cash Drawer', 'mark' => 'K', 'route' => $canViewCashDrawer ? route('cash-drawer.index') : null, 'active' => request()->routeIs('cash-drawer.*'), 'enabled' => $canViewCashDrawer],
        ['label' => 'Reports', 'mark' => 'A', 'route' => $canViewReports ? route('reports.index') : null, 'active' => request()->routeIs('reports.*'), 'enabled' => $canViewReports],
        ['label' => 'Accounting', 'mark' => 'J', 'route' => $canViewAccounting ? route('accounting.index') : null, 'active' => request()->routeIs('accounting.*'), 'enabled' => $canViewAccounting],
        ['label' => 'Access', 'mark' => 'U', 'route' => $canManageAccess ? route('access.index') : null, 'active' => request()->routeIs('access.*'), 'enabled' => $canManageAccess],
        ['label' => 'Settings', 'mark' => 'G', 'route' => $canManageSettings ? route('settings.index') : null, 'active' => request()->routeIs('settings.*'), 'enabled' => $canManageSettings],
    ];
@endphp

<div class="app-background min-h-screen p-3 sm:p-4 lg:p-6">
    <div class="app-frame">
    <aside class="clinical-sidebar hidden w-64 shrink-0 flex-col text-white lg:flex">
        <div class="border-b border-white/10 px-5 py-5">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-pharma-600 text-xl font-black leading-none tracking-normal text-white shadow-lg shadow-pharma-600/20">
                    +
                </div>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-white">{{ config('app.name') }}</p>
                    <p class="mt-0.5 text-[11px] font-semibold uppercase text-slate-400">{{ config('pharmacy.store_code') }}</p>
                </div>
            </div>
            <div class="mt-5 rounded-lg border border-white/10 bg-white/[0.04] px-3 py-3">
                <p class="truncate text-sm font-semibold text-white">{{ auth()->user()?->name ?? 'Local operator' }}</p>
                <p class="mt-1 text-xs text-slate-400">Offline pharmacy workspace</p>
            </div>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4 text-sm font-medium">
            <p class="px-3 pb-2 text-[11px] font-semibold uppercase text-slate-500">Main Menu</p>

            @foreach ($navItems as $item)
                @if ($item['enabled'])
                    <a href="{{ $item['route'] }}" class="group flex items-center gap-3 rounded-lg px-3 py-2.5 transition {{ $item['active'] ? 'bg-pharma-600/15 text-white shadow-sm ring-1 ring-pharma-600/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                        <span class="flex h-7 w-7 items-center justify-center rounded-md text-xs font-bold {{ $item['active'] ? 'bg-pharma-600 text-white' : 'bg-white/5 text-slate-400 group-hover:bg-white/10 group-hover:text-white' }}">{{ $item['mark'] }}</span>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @else
                    <div class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-slate-500">
                        <span class="flex h-7 w-7 items-center justify-center rounded-md bg-white/[0.03] text-xs font-bold text-slate-600">{{ $item['mark'] }}</span>
                        <span>{{ $item['label'] }}</span>
                    </div>
                @endif
            @endforeach
        </nav>

        <div class="border-t border-white/10 p-3">
            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-semibold text-slate-300 transition hover:bg-white/5 hover:text-white">
                        <span class="flex h-7 w-7 items-center justify-center rounded-md bg-white/[0.04] text-xs font-bold">O</span>
                        Sign Out
                    </button>
                </form>
            @endauth
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="clinical-header sticky top-0 z-20 border-b border-slate-200/70 backdrop-blur-xl">
            <div class="px-4 py-3 lg:px-6">
                <div class="flex min-h-14 flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        @if ($sectionLabel)
                            <p class="section-kicker">{{ $sectionLabel }}</p>
                        @endif
                        <h1 class="truncate text-2xl font-semibold tracking-normal text-ink-950">{{ $pageTitle }}</h1>
                    </div>

                    @isset($actions)
                        <div class="flex flex-wrap items-center gap-2">
                            {{ $actions }}
                        </div>
                    @endisset
                </div>

                <nav class="mt-3 flex gap-2 overflow-x-auto lg:hidden">
                    @foreach ($navItems as $item)
                        @if ($item['enabled'])
                            <a href="{{ $item['route'] }}" class="whitespace-nowrap rounded-lg px-3 py-2 text-sm font-semibold transition {{ $item['active'] ? 'bg-pharma-600 text-white' : 'bg-white text-ink-700 hover:bg-medical-50' }}">
                                {{ $item['label'] }}
                            </a>
                        @endif
                    @endforeach
                </nav>
            </div>
        </header>

        <main class="flex-1 bg-[#f4f6fb] px-4 py-5 lg:px-6">
            {{ $slot }}
        </main>
    </div>
    </div>
</div>
