<?php

namespace Tests\Feature;

use App\Livewire\SalesInvoiceForm;
use App\Livewire\SalesInvoiceIndex;
use App\Livewire\SalesReturnForm;
use App\Models\ControlledMedicineRegisterEntry;
use App\Models\Customer;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\TaxRate;
use App\Models\User;
use App\Support\FirstRunSetup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ControlledMedicineRegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_controlled_medicine_register(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());

        $this->actingAs($owner)
            ->get('/controlled-medicines')
            ->assertOk()
            ->assertSee('Controlled Medicines');
    }

    public function test_controlled_sale_cancel_and_return_create_register_entries(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);

        $batch = $this->controlledBatch($owner);
        [$customer, $doctor, $patient, $prescription, $prescriptionItem] = $this->clinicalContext($owner, $batch);

        Livewire::test(SalesInvoiceForm::class)
            ->set('sale.invoice_number', 'SI-CM-001')
            ->set('sale.customer_id', (string) $customer->id)
            ->set('sale.patient_id', (string) $patient->id)
            ->set('sale.doctor_id', (string) $doctor->id)
            ->set('sale.prescription_id', (string) $prescription->id)
            ->set('sale.payment_method', 'cash')
            ->set('items.0.product_batch_id', (string) $batch->id)
            ->call('useBatch', 0)
            ->set('items.0.prescription_item_id', (string) $prescriptionItem->id)
            ->set('items.0.quantity', '2.000000')
            ->set('sale.paid_amount', '50.40')
            ->call('save')
            ->assertHasNoErrors();

        $invoice = SalesInvoice::query()->with('items')->latest('id')->firstOrFail();
        $invoiceItem = $invoice->items->firstOrFail();

        $saleEntry = ControlledMedicineRegisterEntry::query()
            ->where('entry_type', ControlledMedicineRegisterEntry::TYPE_SALE)
            ->firstOrFail();

        $this->assertSame($batch->product_id, $saleEntry->product_id);
        $this->assertSame($customer->id, $saleEntry->customer_id);
        $this->assertSame($patient->id, $saleEntry->patient_id);
        $this->assertSame($doctor->id, $saleEntry->doctor_id);
        $this->assertSame($prescription->id, $saleEntry->prescription_id);
        $this->assertSame($invoice->id, $saleEntry->sales_invoice_id);
        $this->assertSame($invoiceItem->id, $saleEntry->sales_invoice_item_id);
        $this->assertSame('2.000000', $saleEntry->quantity_effect);

        Livewire::test(SalesReturnForm::class, ['salesInvoice' => $invoice])
            ->set('items.0.quantity', '1.000000')
            ->set('return.refund_method', 'cash')
            ->call('save')
            ->assertHasNoErrors();

        $salesReturn = SalesReturn::query()->with('items')->firstOrFail();
        $returnEntry = ControlledMedicineRegisterEntry::query()
            ->where('entry_type', ControlledMedicineRegisterEntry::TYPE_SALE_RETURN)
            ->firstOrFail();

        $this->assertSame($salesReturn->id, $returnEntry->sales_return_id);
        $this->assertSame($salesReturn->items->first()->id, $returnEntry->sales_return_item_id);
        $this->assertSame('-1.000000', $returnEntry->quantity_effect);

        $freshBatch = $this->controlledBatch($owner, [
            'product_name' => 'Controlled Syrup',
            'sku' => 'CM-SYRUP-001',
            'batch_number' => 'CM-BATCH-002',
        ]);
        [$freshCustomer, $freshDoctor, $freshPatient, $freshPrescription, $freshPrescriptionItem] = $this->clinicalContext($owner, $freshBatch, '002');

        Livewire::test(SalesInvoiceForm::class)
            ->set('sale.invoice_number', 'SI-CM-CANCEL-001')
            ->set('sale.customer_id', (string) $freshCustomer->id)
            ->set('sale.patient_id', (string) $freshPatient->id)
            ->set('sale.doctor_id', (string) $freshDoctor->id)
            ->set('sale.prescription_id', (string) $freshPrescription->id)
            ->set('sale.payment_method', 'cash')
            ->set('items.0.product_batch_id', (string) $freshBatch->id)
            ->call('useBatch', 0)
            ->set('items.0.prescription_item_id', (string) $freshPrescriptionItem->id)
            ->set('items.0.quantity', '1.000000')
            ->set('sale.paid_amount', '25.20')
            ->call('save')
            ->assertHasNoErrors();

        $cancelInvoice = SalesInvoice::query()->where('invoice_number', 'SI-CM-CANCEL-001')->with('items')->firstOrFail();

        Livewire::test(SalesInvoiceIndex::class)
            ->call('cancelInvoice', $cancelInvoice->id);

        $cancelEntry = ControlledMedicineRegisterEntry::query()
            ->where('entry_type', ControlledMedicineRegisterEntry::TYPE_SALE_CANCEL)
            ->where('sales_invoice_item_id', $cancelInvoice->items->first()->id)
            ->firstOrFail();

        $this->assertSame('-1.000000', $cancelEntry->quantity_effect);

        $this->get(route('controlled-medicines.show', $saleEntry))
            ->assertOk()
            ->assertSee('Controlled Register Detail')
            ->assertSee('Bill Dispense');
    }

    public function test_user_without_register_permission_is_forbidden(): void
    {
        app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/controlled-medicines')
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function controlledBatch(User $owner, array $overrides = []): ProductBatch
    {
        $taxRate = TaxRate::query()->create([
            'name' => 'Controlled Tax '.($overrides['sku'] ?? '001'),
            'rate_percent' => '5.00',
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $product = Product::query()->create([
            'name' => $overrides['product_name'] ?? 'Controlled Tablet',
            'sku' => $overrides['sku'] ?? 'CM-TAB-001',
            'tax_rate_id' => $taxRate->id,
            'prescription_required' => true,
            'controlled_medicine' => true,
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
            'batch_number' => $overrides['batch_number'] ?? 'CM-BATCH-001',
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
     * @return array{0: Customer, 1: Doctor, 2: Patient, 3: Prescription, 4: PrescriptionItem}
     */
    private function clinicalContext(User $owner, ProductBatch $batch, string $suffix = '001'): array
    {
        $customer = Customer::query()->create([
            'name' => 'Controlled Customer '.$suffix,
            'code' => 'CM-CUST-'.$suffix,
            'phone' => '900001'.$suffix,
            'opening_balance' => '0.00',
            'outstanding_balance' => '0.00',
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $doctor = Doctor::query()->create([
            'name' => 'Dr Controlled '.$suffix,
            'registration_number' => 'DOC-CM-'.$suffix,
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $patient = Patient::query()->create([
            'customer_id' => $customer->id,
            'primary_doctor_id' => $doctor->id,
            'primary_doctor_name' => $doctor->name,
            'full_name' => 'Controlled Patient '.$suffix,
            'patient_code' => 'CM-PAT-'.$suffix,
            'phone' => '900002'.$suffix,
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $prescription = Prescription::query()->create([
            'prescription_number' => 'CM-RX-'.$suffix,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'patient_name_snapshot' => $patient->full_name,
            'patient_phone_snapshot' => $patient->phone,
            'doctor_name_snapshot' => $doctor->name,
            'prescription_date' => '2026-08-12',
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
        ]);

        return [$customer, $doctor, $patient, $prescription, $prescriptionItem];
    }
}
