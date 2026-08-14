<?php

namespace Tests\Feature;

use App\Livewire\PrescriptionForm;
use App\Livewire\SalesInvoiceForm;
use App\Livewire\SalesInvoiceIndex;
use App\Livewire\SalesReturnForm;
use App\Models\Customer;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Support\FirstRunSetup;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PrescriptionRefillTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_refill_tracked_prescription_and_view_tracker_pages(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);

        $patient = Patient::query()->create([
            'full_name' => 'Refill Patient',
            'patient_code' => 'RF-PAT-001',
            'phone' => '9000000210',
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $product = Product::query()->create([
            'name' => 'Refill Capsule',
            'sku' => 'RF-CAP-001',
            'prescription_required' => true,
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $product->units()->create([
            'unit_name' => 'Capsule',
            'unit_code' => 'CAP',
            'conversion_factor' => '1',
            'is_base' => true,
            'sellable' => true,
            'purchasable' => true,
        ]);

        $doctor = Doctor::query()->create([
            'name' => 'Dr Refill',
            'registration_number' => 'DOC-RF-001',
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        Livewire::test(PrescriptionForm::class)
            ->set('prescription.prescription_number', 'rx-refill-001')
            ->set('prescription.patient_id', (string) $patient->id)
            ->set('prescription.doctor_id', (string) $doctor->id)
            ->set('prescription.prescription_date', '2026-08-12')
            ->set('items.0.product_id', (string) $product->id)
            ->call('useProduct', 0)
            ->set('items.0.dosage_instructions', '1 capsule every evening')
            ->set('items.0.quantity_prescribed', '6.000000')
            ->set('items.0.refill_interval_days', '30')
            ->set('items.0.refill_reminder_days', '5')
            ->call('save')
            ->assertHasNoErrors();

        $prescriptionItem = PrescriptionItem::query()->firstOrFail();

        $this->assertSame(30, $prescriptionItem->refill_interval_days);
        $this->assertSame(5, $prescriptionItem->refill_reminder_days);
        $this->assertNull($prescriptionItem->last_dispensed_on);
        $this->assertNull($prescriptionItem->next_refill_due_on);

        $this->get(route('prescription-refills.index'))
            ->assertOk()
            ->assertSee('Refill Tracker')
            ->assertSee('Refill Capsule')
            ->assertSee('Pending first fill');

        $this->get(route('prescription-refills.show', $prescriptionItem))
            ->assertOk()
            ->assertSee('Refill Detail')
            ->assertSee('Refill Capsule')
            ->assertSee('30');
    }

    public function test_refill_tracker_updates_from_sale_and_partial_return(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);

        $batch = $this->batch($owner, [
            'product_name' => 'Refill Tablet',
            'sku' => 'RF-TAB-002',
            'batch_number' => 'RF-BATCH-002',
        ]);
        [$customer, $doctor, $patient, $prescription, $prescriptionItem] = $this->clinicalContext($owner, $batch, [
            'prescription_number' => 'RF-RX-002',
            'refill_interval_days' => 3,
            'refill_reminder_days' => 0,
        ]);

        Livewire::test(SalesInvoiceForm::class)
            ->set('sale.invoice_number', 'SI-RF-002')
            ->set('sale.invoice_date', '2026-08-10')
            ->set('sale.customer_id', (string) $customer->id)
            ->set('sale.patient_id', (string) $patient->id)
            ->set('sale.doctor_id', (string) $doctor->id)
            ->set('sale.prescription_id', (string) $prescription->id)
            ->set('sale.payment_method', 'cash')
            ->set('items.0.product_batch_id', (string) $batch->id)
            ->call('useBatch', 0)
            ->set('items.0.prescription_item_id', (string) $prescriptionItem->id)
            ->set('items.0.quantity', '2.000000')
            ->set('sale.paid_amount', '48.00')
            ->call('save')
            ->assertHasNoErrors();

        $prescriptionItem->refresh();

        $this->assertSame('2026-08-10', $prescriptionItem->last_dispensed_on?->toDateString());
        $this->assertSame('2026-08-13', $prescriptionItem->next_refill_due_on?->toDateString());
        $this->assertSame(PrescriptionItem::REFILL_STATUS_DUE, $prescriptionItem->refillStatus(Carbon::parse('2026-08-13')));

        $invoice = SalesInvoice::query()->where('invoice_number', 'SI-RF-002')->firstOrFail();

        Livewire::test(SalesReturnForm::class, ['salesInvoice' => $invoice])
            ->set('items.0.quantity', '1.000000')
            ->set('return.refund_method', 'cash')
            ->call('save')
            ->assertHasNoErrors();

        $prescriptionItem->refresh();

        $this->assertSame('1.000000', $prescriptionItem->quantity_dispensed);
        $this->assertSame('2026-08-10', $prescriptionItem->last_dispensed_on?->toDateString());
        $this->assertSame('2026-08-13', $prescriptionItem->next_refill_due_on?->toDateString());
        $this->assertSame(PrescriptionItem::REFILL_STATUS_DUE, $prescriptionItem->refillStatus(Carbon::parse('2026-08-13')));
    }

    public function test_cancelled_bill_clears_refill_dates_when_no_net_dispense_remains(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);

        $batch = $this->batch($owner, [
            'product_name' => 'Refill Syrup',
            'sku' => 'RF-SYR-003',
            'batch_number' => 'RF-BATCH-003',
        ]);
        [$customer, $doctor, $patient, $prescription, $prescriptionItem] = $this->clinicalContext($owner, $batch, [
            'prescription_number' => 'RF-RX-003',
            'refill_interval_days' => 7,
            'refill_reminder_days' => 2,
        ]);

        Livewire::test(SalesInvoiceForm::class)
            ->set('sale.invoice_number', 'SI-RF-CANCEL-003')
            ->set('sale.invoice_date', '2026-08-12')
            ->set('sale.customer_id', (string) $customer->id)
            ->set('sale.patient_id', (string) $patient->id)
            ->set('sale.doctor_id', (string) $doctor->id)
            ->set('sale.prescription_id', (string) $prescription->id)
            ->set('sale.payment_method', 'cash')
            ->set('items.0.product_batch_id', (string) $batch->id)
            ->call('useBatch', 0)
            ->set('items.0.prescription_item_id', (string) $prescriptionItem->id)
            ->set('items.0.quantity', '1.000000')
            ->set('sale.paid_amount', '24.00')
            ->call('save')
            ->assertHasNoErrors();

        $invoice = SalesInvoice::query()->where('invoice_number', 'SI-RF-CANCEL-003')->firstOrFail();

        Livewire::test(SalesInvoiceIndex::class)
            ->call('cancelInvoice', $invoice->id);

        $prescriptionItem->refresh();

        $this->assertSame('0.000000', $prescriptionItem->quantity_dispensed);
        $this->assertNull($prescriptionItem->last_dispensed_on);
        $this->assertNull($prescriptionItem->next_refill_due_on);
        $this->assertSame(PrescriptionItem::REFILL_STATUS_PENDING, $prescriptionItem->refillStatus());
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function batch(User $owner, array $overrides = []): ProductBatch
    {
        $product = Product::query()->create([
            'name' => $overrides['product_name'] ?? 'Refill Product',
            'sku' => $overrides['sku'] ?? 'RF-PROD-001',
            'prescription_required' => true,
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $product->units()->create([
            'unit_name' => 'Tablet',
            'unit_code' => 'TAB',
            'conversion_factor' => '1',
            'is_base' => true,
            'sellable' => true,
            'purchasable' => true,
        ]);

        return ProductBatch::query()->create([
            'product_id' => $product->id,
            'batch_number' => $overrides['batch_number'] ?? 'RF-BATCH-001',
            'manufactured_on' => '2026-01-01',
            'expires_on' => '2028-12-31',
            'mrp' => '25.00',
            'purchase_rate' => '10.00',
            'sale_rate' => '24.00',
            'available_quantity' => '12.000000',
            'is_blocked' => false,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{0: Customer, 1: Doctor, 2: Patient, 3: Prescription, 4: PrescriptionItem}
     */
    private function clinicalContext(User $owner, ProductBatch $batch, array $overrides = []): array
    {
        $customer = Customer::query()->create([
            'name' => 'Refill Customer '.($overrides['prescription_number'] ?? '001'),
            'code' => 'RF-CUST-'.($overrides['prescription_number'] ?? '001'),
            'phone' => '9000030001',
            'opening_balance' => '0.00',
            'outstanding_balance' => '0.00',
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $doctor = Doctor::query()->create([
            'name' => 'Dr Refill Context '.($overrides['prescription_number'] ?? '001'),
            'registration_number' => 'DOC-'.($overrides['prescription_number'] ?? '001'),
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $patient = Patient::query()->create([
            'customer_id' => $customer->id,
            'primary_doctor_id' => $doctor->id,
            'primary_doctor_name' => $doctor->name,
            'full_name' => 'Refill Patient '.($overrides['prescription_number'] ?? '001'),
            'patient_code' => 'RF-PAT-'.($overrides['prescription_number'] ?? '001'),
            'phone' => '9000030002',
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $prescription = Prescription::query()->create([
            'prescription_number' => $overrides['prescription_number'] ?? 'RF-RX-001',
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'patient_name_snapshot' => $patient->full_name,
            'patient_phone_snapshot' => $patient->phone,
            'doctor_name_snapshot' => $doctor->name,
            'prescription_date' => '2026-08-01',
            'valid_until' => '2026-09-30',
            'status' => Prescription::STATUS_OPEN,
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $prescriptionItem = $prescription->items()->create([
            'product_id' => $batch->product_id,
            'medicine_name_snapshot' => $batch->product->name,
            'unit_name_snapshot' => 'Tablet',
            'dosage_instructions' => '1 unit twice daily',
            'quantity_prescribed' => '5.000000',
            'quantity_dispensed' => '0.000000',
            'refill_interval_days' => $overrides['refill_interval_days'] ?? 30,
            'refill_reminder_days' => $overrides['refill_reminder_days'] ?? 3,
        ]);

        return [$customer, $doctor, $patient, $prescription, $prescriptionItem];
    }
}
