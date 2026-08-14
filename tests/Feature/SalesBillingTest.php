<?php

namespace Tests\Feature;

use App\Livewire\SalesInvoiceForm;
use App\Livewire\SalesInvoiceIndex;
use App\Livewire\SalesReturnForm;
use App\Models\AuditEvent;
use App\Models\Customer;
use App\Models\Doctor;
use App\Models\HeldSalesBill;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductBatch;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesReturn;
use App\Models\StockMovement;
use App\Models\TaxRate;
use App\Models\User;
use App\Support\FirstRunSetup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SalesBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_setup_redirects_guest_billing_requests_to_login(): void
    {
        app(FirstRunSetup::class)->complete($this->firstRunPayload());

        $this->get('/billing/sales')
            ->assertRedirect(route('login'));
    }

    public function test_owner_can_open_billing_pages(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);
        $this->batch($owner);

        $this->get('/billing/sales')
            ->assertOk()
            ->assertSee('Billing')
            ->assertSee('New Bill');

        $this->get('/billing/sales/create')
            ->assertOk()
            ->assertSee('New Bill')
            ->assertSee('Customer, Patient, Prescription and Payment')
            ->assertSee('Batch, Quantity and Price');
    }

    public function test_owner_can_open_sales_return_pages(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);
        $batch = $this->batch($owner);

        $invoice = $this->finalizeInvoice($batch, [
            'invoice_number' => 'SI-RETURN-PAGE-001',
            'quantity' => '2',
            'paid_amount' => '48.00',
        ]);

        $this->get(route('sales-invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Process Return');

        $this->get(route('sales-returns.create', $invoice))
            ->assertOk()
            ->assertSee('Sales Return')
            ->assertSee('Returned Quantities')
            ->assertSee('Full Remaining');
    }

    public function test_owner_can_finalize_bill_and_reduce_batch_stock(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);
        $batch = $this->batch($owner);

        Livewire::test(SalesInvoiceForm::class)
            ->set('sale.invoice_number', 'si-test-001')
            ->set('sale.invoice_date', '2026-08-12')
            ->set('sale.customer_name', 'Walk In Test')
            ->set('sale.customer_phone', '9999999999')
            ->set('sale.payment_method', 'cash')
            ->set('items.0.product_batch_id', (string) $batch->id)
            ->call('useBatch', 0)
            ->set('items.0.quantity', '2')
            ->set('items.0.unit_price', '24.00')
            ->set('items.0.discount_amount', '2.00')
            ->set('sale.paid_amount', '50.00')
            ->call('save')
            ->assertHasNoErrors();

        $invoice = SalesInvoice::query()->with('items')->firstOrFail();
        $batch->refresh();
        $movement = StockMovement::query()->where('movement_type', 'sale')->firstOrFail();

        $this->assertSame('SI-TEST-001', $invoice->invoice_number);
        $this->assertSame('finalized', $invoice->status);
        $this->assertSame('48.00', $invoice->subtotal_amount);
        $this->assertSame('2.00', $invoice->discount_amount);
        $this->assertSame('2.30', $invoice->tax_amount);
        $this->assertSame('48.30', $invoice->total_amount);
        $this->assertSame('50.00', $invoice->paid_amount);
        $this->assertSame('1.70', $invoice->change_amount);
        $this->assertSame('10.000000', $batch->available_quantity);
        $this->assertSame('-2.000000', $movement->quantity);
        $this->assertSame(SalesInvoice::class, $movement->source_type);
        $this->assertSame($invoice->id, $movement->source_id);
        $this->assertSame(1, SalesInvoiceItem::query()->count());
        $this->assertSame(1, AuditEvent::query()->where('action', 'sales_invoice.created')->count());
    }

    public function test_cancelling_bill_reverses_stock_without_deleting_history(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);
        $batch = $this->batch($owner);

        Livewire::test(SalesInvoiceForm::class)
            ->set('sale.invoice_number', 'SI-CANCEL-001')
            ->set('items.0.product_batch_id', (string) $batch->id)
            ->call('useBatch', 0)
            ->set('items.0.quantity', '3')
            ->set('items.0.unit_price', '20.00')
            ->set('sale.paid_amount', '63.00')
            ->call('save')
            ->assertHasNoErrors();

        $invoice = SalesInvoice::query()->firstOrFail();

        $this->get(route('sales-invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Bill Detail')
            ->assertSee('SI-CANCEL-001');

        $this->get(route('sales-invoices.receipt', $invoice))
            ->assertOk()
            ->assertSee('Receipt')
            ->assertSee('SI-CANCEL-001')
            ->assertSee('Print');

        Livewire::test(SalesInvoiceIndex::class)
            ->call('cancelInvoice', $invoice->id);

        $invoice->refresh();
        $batch->refresh();

        $this->assertSame('cancelled', $invoice->status);
        $this->assertSame('12.000000', $batch->available_quantity);
        $this->assertSame(2, StockMovement::query()->count());
        $this->assertSame(1, StockMovement::query()->where('movement_type', 'sale_cancel')->count());
        $this->assertSame(1, AuditEvent::query()->where('action', 'sales_invoice.cancelled')->count());
    }

    public function test_sale_cannot_exceed_available_batch_quantity(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);
        $batch = $this->batch($owner);

        Livewire::test(SalesInvoiceForm::class)
            ->set('items.0.product_batch_id', (string) $batch->id)
            ->call('useBatch', 0)
            ->set('items.0.quantity', '99')
            ->set('items.0.unit_price', '20.00')
            ->call('save')
            ->assertHasErrors(['items.0.quantity']);

        $this->assertSame(0, SalesInvoice::query()->count());
        $this->assertSame(0, StockMovement::query()->count());
        $this->assertSame('12.000000', $batch->refresh()->available_quantity);
    }

    public function test_cashier_can_scan_barcode_hold_and_resume_bill(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);
        $batch = $this->batch($owner);

        ProductBarcode::query()->create([
            'product_id' => $batch->product_id,
            'barcode' => '8900000000999',
            'barcode_type' => 'EAN13',
            'is_primary' => true,
        ]);

        Livewire::test(SalesInvoiceForm::class)
            ->set('quickScan', '8900000000999')
            ->call('applyQuickScan')
            ->assertSet('items.0.product_batch_id', (string) $batch->id)
            ->assertSet('items.0.quantity', '1')
            ->assertSee('Added Sales Tablet')
            ->set('sale.customer_name', 'Held Customer')
            ->call('holdBill')
            ->assertHasNoErrors();

        $this->assertSame(1, HeldSalesBill::query()->count());
        $this->assertSame(0, SalesInvoice::query()->count());
        $this->assertSame('12.000000', $batch->refresh()->available_quantity);

        $held = HeldSalesBill::query()->firstOrFail();

        Livewire::test(SalesInvoiceForm::class)
            ->set('selectedHoldId', (string) $held->id)
            ->call('resumeHold')
            ->assertSet('sale.customer_name', 'Held Customer')
            ->assertSet('items.0.product_batch_id', (string) $batch->id);

        $this->assertSame(0, HeldSalesBill::query()->count());
    }

    public function test_cashier_can_finalize_bill_with_linked_customer_and_patient_records(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);
        $batch = $this->batch($owner);

        $customer = Customer::query()->create([
            'name' => 'Linked Customer',
            'code' => 'LC-001',
            'phone' => '9000000001',
            'opening_balance' => '0.00',
            'outstanding_balance' => '0.00',
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $patient = Patient::query()->create([
            'customer_id' => $customer->id,
            'full_name' => 'Linked Patient',
            'patient_code' => 'LP-001',
            'phone' => '9000000002',
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        Livewire::test(SalesInvoiceForm::class)
            ->set('sale.invoice_number', 'SI-LINK-001')
            ->set('sale.invoice_date', '2026-08-12')
            ->set('sale.patient_id', (string) $patient->id)
            ->set('sale.payment_method', 'cash')
            ->set('items.0.product_batch_id', (string) $batch->id)
            ->call('useBatch', 0)
            ->set('items.0.quantity', '1')
            ->set('sale.paid_amount', '25.20')
            ->call('save')
            ->assertHasNoErrors();

        $invoice = SalesInvoice::query()->with(['customer', 'patient'])->firstOrFail();

        $this->assertSame($customer->id, $invoice->customer_id);
        $this->assertSame($patient->id, $invoice->patient_id);
        $this->assertSame('Linked Customer', $invoice->customer_name);
        $this->assertSame('Linked Patient', $invoice->patient_name);

        $this->get(route('sales-invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Linked Customer')
            ->assertSee('Linked Patient');

        $this->get(route('sales-invoices.receipt', $invoice))
            ->assertOk()
            ->assertSee('Linked Patient');
    }

    public function test_prescription_required_product_requires_prescription_and_updates_dispensed_quantity(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);
        $batch = $this->batch($owner, ['prescription_required' => true]);

        $customer = Customer::query()->create([
            'name' => 'RX Customer',
            'code' => 'RX-CUST-001',
            'phone' => '9000000101',
            'opening_balance' => '0.00',
            'outstanding_balance' => '0.00',
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $doctor = Doctor::query()->create([
            'name' => 'Dr Billing',
            'registration_number' => 'DOC-BILL-001',
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $patient = Patient::query()->create([
            'customer_id' => $customer->id,
            'primary_doctor_id' => $doctor->id,
            'primary_doctor_name' => $doctor->name,
            'full_name' => 'RX Patient',
            'patient_code' => 'RX-PAT-002',
            'phone' => '9000000102',
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $prescription = Prescription::query()->create([
            'prescription_number' => 'RX-BILL-001',
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

        $rxItem = $prescription->items()->create([
            'product_id' => $batch->product_id,
            'medicine_name_snapshot' => 'Sales Tablet',
            'unit_name_snapshot' => 'Tablet',
            'dosage_instructions' => '1 tablet twice daily',
            'quantity_prescribed' => '5.000000',
            'quantity_dispensed' => '0.000000',
        ]);

        Livewire::test(SalesInvoiceForm::class)
            ->set('sale.invoice_number', 'SI-RX-FAIL-001')
            ->set('items.0.product_batch_id', (string) $batch->id)
            ->call('useBatch', 0)
            ->set('items.0.quantity', '1')
            ->set('sale.paid_amount', '25.20')
            ->call('save')
            ->assertHasErrors([
                'sale.prescription_id',
                'items.0.prescription_item_id',
            ]);

        Livewire::test(SalesInvoiceForm::class)
            ->set('sale.invoice_number', 'SI-RX-001')
            ->set('sale.prescription_id', (string) $prescription->id)
            ->set('sale.payment_method', 'cash')
            ->set('items.0.product_batch_id', (string) $batch->id)
            ->call('useBatch', 0)
            ->set('items.0.prescription_item_id', (string) $rxItem->id)
            ->set('items.0.quantity', '2')
            ->set('sale.paid_amount', '50.40')
            ->call('save')
            ->assertHasNoErrors();

        $invoice = SalesInvoice::query()->with(['prescription', 'doctor', 'items.prescriptionItem'])->latest('id')->firstOrFail();

        $this->assertSame($prescription->id, $invoice->prescription_id);
        $this->assertSame($doctor->id, $invoice->doctor_id);
        $this->assertSame('RX-BILL-001', $invoice->prescription_number);
        $this->assertSame('Dr Billing', $invoice->doctor_name);
        $this->assertSame($rxItem->id, $invoice->items->first()->prescription_item_id);
        $this->assertSame('2.000000', $rxItem->fresh()->quantity_dispensed);
        $this->assertSame(Prescription::STATUS_PARTIAL, $prescription->fresh()->status);

        Livewire::test(SalesInvoiceIndex::class)
            ->call('cancelInvoice', $invoice->id);

        $this->assertSame('0.000000', $rxItem->fresh()->quantity_dispensed);
        $this->assertSame(Prescription::STATUS_OPEN, $prescription->fresh()->status);
    }

    public function test_cashier_can_create_partial_sales_return_and_optional_restock(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);
        $batch = $this->batch($owner);

        $invoice = $this->finalizeInvoice($batch, [
            'invoice_number' => 'SI-RETURN-001',
            'quantity' => '4',
            'discount_amount' => '4.00',
            'paid_amount' => '96.20',
        ]);

        $invoiceItem = $invoice->items()->firstOrFail();

        Livewire::test(SalesReturnForm::class, ['salesInvoice' => $invoice])
            ->set('items.0.quantity', '1.500000')
            ->set('items.0.restock_to_inventory', true)
            ->set('return.refund_method', 'cash')
            ->call('save')
            ->assertHasNoErrors();

        $salesReturn = SalesReturn::query()->with('items')->firstOrFail();
        $returnItem = $salesReturn->items->firstOrFail();
        $batch->refresh();

        $this->assertSame('SI-RETURN-001', $salesReturn->salesInvoice->invoice_number);
        $this->assertSame('1.500000', $returnItem->quantity);
        $this->assertTrue($returnItem->restock_to_inventory);
        $this->assertSame('1.50', $returnItem->discount_amount);
        $this->assertSame('36.23', $salesReturn->total_amount);
        $this->assertSame('36.23', $salesReturn->refund_amount);
        $this->assertSame('9.500000', $batch->available_quantity);
        $this->assertSame(1, StockMovement::query()->where('movement_type', 'sale_return_restock')->count());
        $this->assertSame(1, AuditEvent::query()->where('action', 'sales_return.created')->count());

        $this->get(route('sales-returns.show', $salesReturn))
            ->assertOk()
            ->assertSee($salesReturn->return_number)
            ->assertSee('Yes');

        $this->get(route('sales-invoices.show', $invoice->fresh()))
            ->assertOk()
            ->assertSee($salesReturn->return_number)
            ->assertDontSee('Cancel this sales invoice and reverse stock?');
    }

    public function test_sale_return_cannot_exceed_remaining_quantity_and_does_not_restock_by_default(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);
        $batch = $this->batch($owner);

        $invoice = $this->finalizeInvoice($batch, [
            'invoice_number' => 'SI-RETURN-LIMIT-001',
            'quantity' => '2',
            'paid_amount' => '50.40',
        ]);

        Livewire::test(SalesReturnForm::class, ['salesInvoice' => $invoice])
            ->set('items.0.quantity', '1.000000')
            ->call('save')
            ->assertHasNoErrors();

        $batch->refresh();
        $this->assertSame('10.000000', $batch->available_quantity);
        $this->assertSame(0, StockMovement::query()->where('movement_type', 'sale_return_restock')->count());

        Livewire::test(SalesReturnForm::class, ['salesInvoice' => $invoice->fresh()])
            ->set('items.0.quantity', '2.000000')
            ->call('save')
            ->assertStatus(422);

        $this->assertSame(1, SalesReturn::query()->count());
        $this->assertSame('10.000000', $batch->refresh()->available_quantity);
    }

    public function test_user_without_sales_permission_is_forbidden(): void
    {
        app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/billing/sales')
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function batch(User $owner, array $overrides = []): ProductBatch
    {
        $taxRate = TaxRate::query()->create([
            'name' => 'Sales Tax',
            'rate_percent' => '5.00',
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $product = Product::query()->create([
            'name' => $overrides['product_name'] ?? 'Sales Tablet',
            'sku' => $overrides['sku'] ?? 'SALE-PROD',
            'tax_rate_id' => $taxRate->id,
            'prescription_required' => (bool) ($overrides['prescription_required'] ?? false),
            'controlled_medicine' => (bool) ($overrides['controlled_medicine'] ?? false),
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
            'batch_number' => $overrides['batch_number'] ?? 'SALE-BATCH',
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
     * @param  array<string, string>  $overrides
     */
    private function finalizeInvoice(ProductBatch $batch, array $overrides = []): SalesInvoice
    {
        Livewire::test(SalesInvoiceForm::class)
            ->set('sale.invoice_number', $overrides['invoice_number'] ?? 'SI-AUTO-001')
            ->set('sale.invoice_date', $overrides['invoice_date'] ?? '2026-08-12')
            ->set('sale.payment_method', $overrides['payment_method'] ?? 'cash')
            ->set('items.0.product_batch_id', (string) $batch->id)
            ->call('useBatch', 0)
            ->set('items.0.quantity', $overrides['quantity'] ?? '1')
            ->set('items.0.unit_price', $overrides['unit_price'] ?? '24.00')
            ->set('items.0.discount_amount', $overrides['discount_amount'] ?? '0.00')
            ->set('sale.paid_amount', $overrides['paid_amount'] ?? '24.00')
            ->call('save')
            ->assertHasNoErrors();

        return SalesInvoice::query()->latest('id')->firstOrFail()->load('items');
    }
}
