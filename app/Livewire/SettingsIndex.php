<?php

namespace App\Livewire;

use App\Models\ApplicationSetting;
use App\Support\FirstRunSetup;
use App\Support\SettingsManager;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SettingsIndex extends Component
{
    public array $store = [];

    public array $pharmacist = [];

    public array $billing = [];

    public array $printing = [];

    public array $operations = [];

    public function mount(FirstRunSetup $setup): void
    {
        abort_unless(auth()->user()?->hasPermission('settings.manage'), 403);

        $store = $setup->primaryStore();
        abort_if(! $store, 422, 'Complete first-run setup before changing settings.');

        $pharmacist = $store->registeredPharmacists()->where('is_primary', true)->first()
            ?? $store->registeredPharmacists()->oldest('id')->first();
        abort_if(! $pharmacist, 422, 'A primary registered pharmacist is required before changing settings.');

        $this->store = $store->only([
            'code',
            'name',
            'legal_name',
            'gstin',
            'pan',
            'drug_license_number',
            'drug_license_valid_until',
            'address_line_1',
            'address_line_2',
            'city',
            'state',
            'postal_code',
            'phone',
            'email',
        ]);
        $this->store['drug_license_valid_until'] = $store->drug_license_valid_until?->format('Y-m-d') ?? '';

        $this->pharmacist = $pharmacist->only([
            'name',
            'registration_number',
            'council_name',
            'license_valid_until',
            'phone',
            'email',
        ]);
        $this->pharmacist['license_valid_until'] = $pharmacist->license_valid_until?->format('Y-m-d') ?? '';

        $this->billing = [
            'invoice_prefix' => ApplicationSetting::getValue('billing.invoice_prefix', 'INV'),
            'financial_year_starts_on' => ApplicationSetting::getValue('billing.financial_year_starts_on', now()->startOfYear()->format('Y-m-d')),
        ];

        $this->printing = [
            'receipt_paper_width_mm' => (string) ApplicationSetting::getValue('printing.receipt_paper_width_mm', 80),
            'receipt_copies' => (string) ApplicationSetting::getValue('printing.receipt_copies', 1),
            'receipt_footer' => ApplicationSetting::getValue('printing.receipt_footer', 'Thank you for visiting.'),
        ];

        $this->operations = [
            'default_printer_name' => ApplicationSetting::getValue('printing.default_printer_name', ''),
            'receipt_printer_name' => ApplicationSetting::getValue('printing.receipt_printer_name', ''),
            'backup_path' => ApplicationSetting::getValue('backup.default_path', ''),
        ];
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->hasPermission('settings.manage'), 403);

        $validated = $this->validate([
            'store.code' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('stores', 'code')->ignore($this->storeId())],
            'store.name' => ['required', 'string', 'max:160'],
            'store.legal_name' => ['nullable', 'string', 'max:180'],
            'store.gstin' => ['nullable', 'string', 'max:20'],
            'store.pan' => ['nullable', 'string', 'max:20'],
            'store.drug_license_number' => ['nullable', 'string', 'max:120'],
            'store.drug_license_valid_until' => ['nullable', 'date'],
            'store.address_line_1' => ['nullable', 'string', 'max:180'],
            'store.address_line_2' => ['nullable', 'string', 'max:180'],
            'store.city' => ['nullable', 'string', 'max:100'],
            'store.state' => ['nullable', 'string', 'max:100'],
            'store.postal_code' => ['nullable', 'string', 'max:20'],
            'store.phone' => ['nullable', 'string', 'max:30'],
            'store.email' => ['nullable', 'email', 'max:160'],
            'pharmacist.name' => ['required', 'string', 'max:160'],
            'pharmacist.registration_number' => ['nullable', 'string', 'max:120'],
            'pharmacist.council_name' => ['nullable', 'string', 'max:160'],
            'pharmacist.license_valid_until' => ['nullable', 'date'],
            'pharmacist.phone' => ['nullable', 'string', 'max:30'],
            'pharmacist.email' => ['nullable', 'email', 'max:160'],
            'billing.invoice_prefix' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-\/]+$/'],
            'billing.financial_year_starts_on' => ['required', 'date'],
            'printing.receipt_paper_width_mm' => ['required', Rule::in(['58', '80'])],
            'printing.receipt_copies' => ['required', 'integer', 'min:1', 'max:3'],
            'printing.receipt_footer' => ['nullable', 'string', 'max:1200'],
            'operations.default_printer_name' => ['nullable', 'string', 'max:160'],
            'operations.receipt_printer_name' => ['nullable', 'string', 'max:160'],
            'operations.backup_path' => ['required', 'string', 'max:500'],
        ]);

        $store = app(FirstRunSetup::class)->primaryStore();
        abort_if(! $store, 422, 'Complete first-run setup before changing settings.');

        $pharmacist = $store->registeredPharmacists()->where('is_primary', true)->first()
            ?? $store->registeredPharmacists()->oldest('id')->first();
        abort_if(! $pharmacist, 422, 'A primary registered pharmacist is required before changing settings.');

        $validated['store']['code'] = strtoupper($validated['store']['code']);

        app(SettingsManager::class)->update(
            $store,
            $pharmacist,
            $validated['store'],
            $validated['pharmacist'],
            $validated['billing'],
            $validated['printing'],
            $validated['operations'],
            auth()->user()
        );

        session()->flash('status', 'Settings saved and audited locally.');
    }

    public function render()
    {
        return view('livewire.settings-index');
    }

    private function storeId(): int
    {
        return (int) (app(FirstRunSetup::class)->primaryStore()?->id ?? 0);
    }
}
