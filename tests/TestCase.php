<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @return array<string, mixed>
     */
    protected function firstRunPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'store' => [
                'code' => 'MAIN',
                'name' => 'Main Medical Store',
                'legal_name' => 'Main Medical Store Pvt Ltd',
                'gstin' => 'GSTIN-PENDING',
                'pan' => 'PAN-PENDING',
                'drug_license_number' => 'DL-PENDING',
                'drug_license_valid_until' => '2027-03-31',
                'address_line_1' => 'Market Road',
                'address_line_2' => null,
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'postal_code' => '411001',
                'phone' => null,
                'email' => null,
            ],
            'pharmacist' => [
                'name' => 'Primary Pharmacist',
                'registration_number' => 'REG-PENDING',
                'council_name' => 'State Pharmacy Council',
                'license_valid_until' => '2027-03-31',
                'phone' => null,
                'email' => null,
            ],
            'billing' => [
                'invoice_prefix' => 'INV',
                'financial_year_starts_on' => '2026-04-01',
            ],
            'operations' => [
                'default_printer_name' => 'Front Counter Printer',
                'receipt_printer_name' => 'Thermal Receipt Printer',
                'backup_path' => 'C:\\MedStoreBackups',
            ],
            'owner' => [
                'name' => 'Store Owner',
                'email' => 'owner@example.test',
                'password' => 'StrongPassword123!',
                'password_confirmation' => 'StrongPassword123!',
            ],
        ], $overrides);
    }
}
