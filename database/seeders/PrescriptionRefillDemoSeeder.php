<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Support\PrescriptionRefillTracker;
use App\Support\SalesBillingManager;
use Illuminate\Database\Seeder;

class PrescriptionRefillDemoSeeder extends Seeder
{
    public function run(): void
    {
        $actor = User::query()->orderBy('id')->first();
        $customer = Customer::query()->where('code', 'CUST-DEMO-001')->first();
        $patient = Patient::query()->where('patient_code', 'PAT-DEMO-001')->first();
        $doctor = Doctor::query()->where('registration_number', 'DOC-DEMO-001')->first();

        if (! $actor || ! $customer || ! $patient || ! $doctor) {
            return;
        }

        $product = Product::query()->firstOrCreate(
            ['sku' => 'RF-DEMO-001'],
            [
                'name' => 'Refill Demo Capsule',
                'prescription_required' => true,
                'is_active' => true,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]
        );

        if (! $product->baseUnit()->exists()) {
            $product->units()->create([
                'unit_name' => 'Capsule',
                'unit_code' => 'CAP',
                'conversion_factor' => '1',
                'is_base' => true,
                'sellable' => true,
                'purchasable' => true,
            ]);
        }

        $batch = ProductBatch::query()->firstOrCreate(
            ['batch_number' => 'RFB-DEMO-001'],
            [
                'product_id' => $product->id,
                'manufactured_on' => '2026-01-01',
                'expires_on' => '2028-12-31',
                'mrp' => '25.00',
                'purchase_rate' => '10.00',
                'sale_rate' => '24.00',
                'available_quantity' => '12.000000',
                'is_blocked' => false,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]
        );

        if (ProductBatch::query()->whereKey($batch->id)->value('product_id') !== $product->id) {
            $batch->update([
                'product_id' => $product->id,
                'updated_by' => $actor->id,
            ]);
        }

        $prescription = Prescription::query()->firstOrCreate(
            ['prescription_number' => 'RX-REFILL-DEMO-001'],
            [
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'patient_name_snapshot' => $patient->full_name,
                'patient_phone_snapshot' => $patient->phone,
                'doctor_name_snapshot' => $doctor->name,
                'prescription_date' => '2026-07-28',
                'valid_until' => '2026-09-30',
                'status' => Prescription::STATUS_OPEN,
                'notes' => 'Local demo refill reminder workflow.',
                'pharmacist_notes' => 'Overdue refill demo entry.',
                'is_active' => true,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]
        );

        $prescriptionItem = $prescription->items()->firstOrCreate(
            ['product_id' => $product->id],
            [
                'medicine_name_snapshot' => $product->name,
                'unit_name_snapshot' => 'Capsule',
                'dosage_instructions' => '1 capsule after breakfast',
                'quantity_prescribed' => '4.000000',
                'quantity_dispensed' => '0.000000',
                'refill_interval_days' => 7,
                'refill_reminder_days' => 2,
                'notes' => 'Demo refill-tracked prescription line.',
            ]
        );

        $prescriptionItem->update([
            'refill_interval_days' => 7,
            'refill_reminder_days' => 2,
        ]);

        if (! SalesInvoice::query()->where('invoice_number', 'SI-RF-DEMO-001')->exists()) {
            app(SalesBillingManager::class)->createFinalizedInvoice([
                'sale' => [
                    'invoice_number' => 'SI-RF-DEMO-001',
                    'invoice_date' => '2026-08-01',
                    'customer_id' => (string) $customer->id,
                    'patient_id' => (string) $patient->id,
                    'doctor_id' => (string) $doctor->id,
                    'prescription_id' => (string) $prescription->id,
                    'payment_method' => 'cash',
                    'paid_amount' => '24.00',
                ],
                'items' => [[
                    'product_batch_id' => (string) $batch->id,
                    'prescription_item_id' => (string) $prescriptionItem->id,
                    'quantity' => '1.000000',
                    'unit_price' => '24.00',
                    'discount_amount' => '0.00',
                    'tax_rate_percent' => '0.00',
                ]],
            ], $actor);
        }

        app(PrescriptionRefillTracker::class)->sync($prescriptionItem->id);
    }
}
