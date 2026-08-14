<?php

namespace App\Support;

use App\Models\ApplicationSetting;
use App\Models\RegisteredPharmacist;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SettingsManager
{
    /**
     * @param  array<string, mixed>  $storeAttributes
     * @param  array<string, mixed>  $pharmacistAttributes
     * @param  array<string, mixed>  $billing
     * @param  array<string, mixed>  $printing
     * @param  array<string, mixed>  $operations
     */
    public function update(
        Store $store,
        RegisteredPharmacist $pharmacist,
        array $storeAttributes,
        array $pharmacistAttributes,
        array $billing,
        array $printing,
        array $operations,
        User $actor
    ): void {
        DB::transaction(function () use ($store, $pharmacist, $storeAttributes, $pharmacistAttributes, $billing, $printing, $operations, $actor): void {
            $before = $this->snapshot($store, $pharmacist);

            $store->update($storeAttributes);
            $pharmacist->update($pharmacistAttributes);

            ApplicationSetting::put('billing.invoice_prefix', Arr::get($billing, 'invoice_prefix'));
            ApplicationSetting::put('billing.financial_year_starts_on', Arr::get($billing, 'financial_year_starts_on'));
            ApplicationSetting::put('printing.default_printer_name', Arr::get($operations, 'default_printer_name'));
            ApplicationSetting::put('printing.receipt_printer_name', Arr::get($operations, 'receipt_printer_name'));
            ApplicationSetting::put('printing.receipt_paper_width_mm', Arr::get($printing, 'receipt_paper_width_mm'), 'integer');
            ApplicationSetting::put('printing.receipt_copies', Arr::get($printing, 'receipt_copies'), 'integer');
            ApplicationSetting::put('printing.receipt_footer', Arr::get($printing, 'receipt_footer'));
            ApplicationSetting::put('backup.default_path', Arr::get($operations, 'backup_path'));

            $after = $this->snapshot($store->refresh(), $pharmacist->refresh());

            app(AuditLogger::class)->record(
                'settings.updated',
                $actor,
                $store,
                [
                    'changed_sections' => $this->changedSections($before, $after),
                ]
            );
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Store $store, RegisteredPharmacist $pharmacist): array
    {
        return [
            'store' => $store->only([
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
            ]),
            'pharmacist' => $pharmacist->only([
                'name',
                'registration_number',
                'council_name',
                'license_valid_until',
                'phone',
                'email',
            ]),
            'settings' => [
                'billing' => [
                    'invoice_prefix' => ApplicationSetting::getValue('billing.invoice_prefix'),
                    'financial_year_starts_on' => ApplicationSetting::getValue('billing.financial_year_starts_on'),
                ],
                'printing' => [
                    'default_printer_name' => ApplicationSetting::getValue('printing.default_printer_name'),
                    'receipt_printer_name' => ApplicationSetting::getValue('printing.receipt_printer_name'),
                    'receipt_paper_width_mm' => ApplicationSetting::getValue('printing.receipt_paper_width_mm'),
                    'receipt_copies' => ApplicationSetting::getValue('printing.receipt_copies'),
                    'receipt_footer' => ApplicationSetting::getValue('printing.receipt_footer'),
                ],
                'backup' => [
                    'default_path' => ApplicationSetting::getValue('backup.default_path'),
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return list<string>
     */
    private function changedSections(array $before, array $after): array
    {
        $sections = [];

        if ($before['store'] !== $after['store']) {
            $sections[] = 'store';
        }

        if ($before['pharmacist'] !== $after['pharmacist']) {
            $sections[] = 'pharmacist';
        }

        if ($before['settings']['billing'] !== $after['settings']['billing']) {
            $sections[] = 'billing';
        }

        if ($before['settings']['printing'] !== $after['settings']['printing']) {
            $sections[] = 'printing';
        }

        if ($before['settings']['backup'] !== $after['settings']['backup']) {
            $sections[] = 'backup';
        }

        return array_values(array_unique($sections));
    }
}
