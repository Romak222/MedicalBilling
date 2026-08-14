<?php

namespace App\Livewire;

use App\Support\FirstRunSetup;
use App\Support\SystemStatus;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class FirstRunWizard extends Component
{
    public int $step = 1;

    public array $store = [
        'code' => '',
        'name' => '',
        'legal_name' => '',
        'gstin' => '',
        'pan' => '',
        'drug_license_number' => '',
        'drug_license_valid_until' => '',
        'address_line_1' => '',
        'address_line_2' => '',
        'city' => '',
        'state' => '',
        'postal_code' => '',
        'phone' => '',
        'email' => '',
    ];

    public array $pharmacist = [
        'name' => '',
        'registration_number' => '',
        'council_name' => '',
        'license_valid_until' => '',
        'phone' => '',
        'email' => '',
    ];

    public array $billing = [
        'invoice_prefix' => '',
        'financial_year_starts_on' => '',
    ];

    public array $operations = [
        'default_printer_name' => '',
        'receipt_printer_name' => '',
        'backup_path' => '',
    ];

    public array $owner = [
        'name' => '',
        'email' => '',
        'password' => '',
        'password_confirmation' => '',
    ];

    public function mount(SystemStatus $systemStatus): void
    {
        $this->store['code'] = config('pharmacy.store_code', 'LOCAL-DEV');
        $this->operations['backup_path'] = $systemStatus->backupPath();
    }

    public function nextStep(): void
    {
        $this->validate($this->rulesForStep($this->step));
        $this->step = min($this->step + 1, 5);
    }

    public function previousStep(): void
    {
        $this->step = max($this->step - 1, 1);
    }

    public function complete(): mixed
    {
        try {
            $validated = $this->validate($this->rules());
        } catch (ValidationException $exception) {
            $this->step = $this->stepForErrorKeys(array_keys($exception->validator->errors()->messages()));

            throw $exception;
        }

        $owner = app(FirstRunSetup::class)->complete($validated);
        auth()->login($owner);

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        session()->flash('status', 'First-run setup completed.');

        return $this->redirectRoute('status', navigate: false);
    }

    public function render()
    {
        return view('livewire.first-run-wizard');
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'store.code' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('stores', 'code')],
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
            'billing.invoice_prefix' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9\\-\\/]+$/'],
            'billing.financial_year_starts_on' => ['required', 'date'],
            'operations.default_printer_name' => ['nullable', 'string', 'max:160'],
            'operations.receipt_printer_name' => ['nullable', 'string', 'max:160'],
            'operations.backup_path' => ['required', 'string', 'max:500'],
            'owner.name' => ['required', 'string', 'max:160'],
            'owner.email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')],
            'owner.password' => ['required', 'string', 'min:12', 'confirmed'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function rulesForStep(int $step): array
    {
        $keys = match ($step) {
            1 => [
                'store.code',
                'store.name',
                'store.legal_name',
                'store.address_line_1',
                'store.address_line_2',
                'store.city',
                'store.state',
                'store.postal_code',
                'store.phone',
                'store.email',
            ],
            2 => [
                'store.gstin',
                'store.pan',
                'store.drug_license_number',
                'store.drug_license_valid_until',
                'pharmacist.name',
                'pharmacist.registration_number',
                'pharmacist.council_name',
                'pharmacist.license_valid_until',
                'pharmacist.phone',
                'pharmacist.email',
            ],
            3 => [
                'billing.invoice_prefix',
                'billing.financial_year_starts_on',
                'operations.default_printer_name',
                'operations.receipt_printer_name',
                'operations.backup_path',
            ],
            4 => [
                'owner.name',
                'owner.email',
                'owner.password',
            ],
            default => array_keys($this->rules()),
        };

        return array_intersect_key($this->rules(), array_flip($keys));
    }

    /**
     * @param  list<string>  $keys
     */
    private function stepForErrorKeys(array $keys): int
    {
        foreach ($keys as $key) {
            return match (true) {
                str_starts_with($key, 'store.gstin'),
                str_starts_with($key, 'store.pan'),
                str_starts_with($key, 'store.drug_license'),
                str_starts_with($key, 'pharmacist.') => 2,
                str_starts_with($key, 'billing.'),
                str_starts_with($key, 'operations.') => 3,
                str_starts_with($key, 'owner.') => 4,
                default => 1,
            };
        }

        return 1;
    }
}
