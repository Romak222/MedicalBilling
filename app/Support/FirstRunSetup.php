<?php

namespace App\Support;

use App\Models\ApplicationSetting;
use App\Models\FirstRunSetupStep;
use App\Models\RegisteredPharmacist;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class FirstRunSetup
{
    public const COMPLETED_SETTING = 'setup.completed';

    public function isComplete(): bool
    {
        if (! $this->settingsTableExists()) {
            return false;
        }

        return ApplicationSetting::getValue(self::COMPLETED_SETTING, false) === true;
    }

    public function primaryStore(): ?Store
    {
        if (! Schema::hasTable('stores')) {
            return null;
        }

        return Store::query()->oldest('id')->first();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function complete(array $payload): User
    {
        if ($this->isComplete()) {
            throw ValidationException::withMessages([
                'setup' => 'First-run setup is already complete.',
            ]);
        }

        return DB::transaction(function () use ($payload): User {
            $store = Store::query()->create($this->storeAttributes($payload));

            RegisteredPharmacist::query()->create([
                ...$this->pharmacistAttributes($payload),
                'store_id' => $store->id,
                'is_primary' => true,
            ]);

            $owner = User::query()->create([
                'name' => Arr::get($payload, 'owner.name'),
                'email' => strtolower((string) Arr::get($payload, 'owner.email')),
                'password' => Arr::get($payload, 'owner.password'),
                'is_owner' => true,
                'created_during_setup' => true,
            ]);

            $this->writeSettings($payload, $store, $owner);
            $this->markStepsComplete($owner);
            app(AccessControl::class)->assignOwnerRole($owner);
            app(AuditLogger::class)->record(
                'setup.completed',
                $owner,
                $store,
                ['steps' => FirstRunSetupStep::query()->count()]
            );

            return $owner;
        });
    }

    public function settingsTableExists(): bool
    {
        return Schema::hasTable('application_settings');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function storeAttributes(array $payload): array
    {
        $store = Arr::get($payload, 'store', []);

        return [
            'code' => strtoupper((string) Arr::get($store, 'code')),
            'name' => Arr::get($store, 'name'),
            'legal_name' => Arr::get($store, 'legal_name'),
            'gstin' => Arr::get($store, 'gstin'),
            'pan' => Arr::get($store, 'pan'),
            'drug_license_number' => Arr::get($store, 'drug_license_number'),
            'drug_license_valid_until' => Arr::get($store, 'drug_license_valid_until') ?: null,
            'address_line_1' => Arr::get($store, 'address_line_1'),
            'address_line_2' => Arr::get($store, 'address_line_2'),
            'city' => Arr::get($store, 'city'),
            'state' => Arr::get($store, 'state'),
            'postal_code' => Arr::get($store, 'postal_code'),
            'phone' => Arr::get($store, 'phone'),
            'email' => Arr::get($store, 'email'),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function pharmacistAttributes(array $payload): array
    {
        $pharmacist = Arr::get($payload, 'pharmacist', []);

        return [
            'name' => Arr::get($pharmacist, 'name'),
            'registration_number' => Arr::get($pharmacist, 'registration_number'),
            'council_name' => Arr::get($pharmacist, 'council_name'),
            'license_valid_until' => Arr::get($pharmacist, 'license_valid_until') ?: null,
            'phone' => Arr::get($pharmacist, 'phone'),
            'email' => Arr::get($pharmacist, 'email'),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function writeSettings(array $payload, Store $store, User $owner): void
    {
        ApplicationSetting::put(self::COMPLETED_SETTING, true, 'boolean');
        ApplicationSetting::put('setup.completed_at', now()->toIso8601String());
        ApplicationSetting::put('setup.store_id', $store->id, 'integer');
        ApplicationSetting::put('setup.owner_user_id', $owner->id, 'integer');
        ApplicationSetting::put('billing.invoice_prefix', Arr::get($payload, 'billing.invoice_prefix'));
        ApplicationSetting::put('billing.financial_year_starts_on', Arr::get($payload, 'billing.financial_year_starts_on'));
        ApplicationSetting::put('printing.default_printer_name', Arr::get($payload, 'operations.default_printer_name'));
        ApplicationSetting::put('printing.receipt_printer_name', Arr::get($payload, 'operations.receipt_printer_name'));
        ApplicationSetting::put('backup.default_path', Arr::get($payload, 'operations.backup_path'));
    }

    private function markStepsComplete(User $owner): void
    {
        $steps = [
            'store_profile' => 'Store profile',
            'legal_identifiers' => 'Legal identifiers',
            'registered_pharmacist' => 'Registered pharmacist',
            'billing_defaults' => 'Billing defaults',
            'printing_backup' => 'Printer and backup paths',
            'owner_account' => 'Owner account',
        ];

        foreach ($steps as $key => $label) {
            FirstRunSetupStep::query()->updateOrCreate(
                ['step_key' => $key],
                [
                    'label' => $label,
                    'completed_at' => now(),
                    'completed_by' => $owner->id,
                ]
            );
        }
    }
}
