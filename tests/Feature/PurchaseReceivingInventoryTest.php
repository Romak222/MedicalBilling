<?php

namespace Tests\Feature;

use App\Livewire\InventoryBatchIndex;
use App\Livewire\PurchaseInvoiceForm;
use App\Livewire\PurchaseInvoiceIndex;
use App\Models\AuditEvent;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\TaxRate;
use App\Models\User;
use App\Support\FirstRunSetup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseReceivingInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_setup_redirects_guest_receiving_requests_to_login(): void
    {
        app(FirstRunSetup::class)->complete($this->firstRunPayload());

        $this->get('/purchases/invoices')
            ->assertRedirect(route('login'));
    }

    public function test_owner_can_open_receiving_and_inventory_pages(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);
        $this->supplier($owner);
        $this->product($owner);

        $this->get('/purchases/invoices')
            ->assertOk()
            ->assertSee('Receive Stock')
            ->assertSee('Add Purchase Invoice')
            ->assertSee('Purchase Orders');

        $this->get('/purchases/invoices/create')
            ->assertOk()
            ->assertSee('Add Purchase Invoice')
            ->assertSee('Supplier Invoice and Receiving Date')
            ->assertSee('Products, Batch, Expiry, MRP and Rates');

        $this->get('/inventory/batches')
            ->assertOk()
            ->assertSee('Inventory Batches')
            ->assertSee('Expiring 90d');
    }

    public function test_purchase_invoice_draft_does_not_create_stock_until_finalized(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);
        $supplier = $this->supplier($owner);
        $product = $this->product($owner);
        $order = $this->purchaseOrder($owner, $supplier);

        Livewire::test(PurchaseInvoiceForm::class)
            ->set('invoice.supplier_id', (string) $supplier->id)
            ->set('invoice.purchase_order_id', (string) $order->id)
            ->set('invoice.invoice_number', 'inv-test-001')
            ->set('invoice.invoice_date', '2026-08-12')
            ->set('invoice.received_on', '2026-08-12')
            ->set('invoice.notes', 'Draft receiving test.')
            ->set('items.0.product_id', (string) $product->id)
            ->call('useProduct', 0)
            ->set('items.0.batch_number', 'batch-a1')
            ->set('items.0.manufactured_on', '2026-01-01')
            ->set('items.0.expires_on', '2027-12-31')
            ->set('items.0.quantity', '10')
            ->set('items.0.free_quantity', '2')
            ->set('items.0.mrp', '25.00')
            ->set('items.0.purchase_rate', '12.50')
            ->set('items.0.sale_rate', '22.00')
            ->set('items.0.discount_amount', '5.00')
            ->call('save')
            ->assertHasNoErrors();

        $invoice = PurchaseInvoice::query()->with('items')->firstOrFail();

        $this->assertSame('INV-TEST-001', $invoice->invoice_number);
        $this->assertSame('draft', $invoice->status);
        $this->assertSame('125.00', $invoice->subtotal_amount);
        $this->assertSame('5.00', $invoice->discount_amount);
        $this->assertSame('6.00', $invoice->tax_amount);
        $this->assertSame('126.00', $invoice->total_amount);
        $this->assertSame('BATCH-A1', $invoice->items->first()->batch_number);
        $this->assertSame(0, ProductBatch::query()->count());
        $this->assertSame(0, StockMovement::query()->count());
        $this->assertSame(1, AuditEvent::query()->where('action', 'purchase_invoice.created')->count());

        Livewire::test(PurchaseInvoiceIndex::class)
            ->call('finalizeInvoice', $invoice->id);

        $invoice->refresh();
        $batch = ProductBatch::query()->firstOrFail();
        $movement = StockMovement::query()->firstOrFail();

        $this->assertSame('finalized', $invoice->status);
        $this->assertSame($product->id, $batch->product_id);
        $this->assertSame('BATCH-A1', $batch->batch_number);
        $this->assertSame('2027-12-31', $batch->expires_on->format('Y-m-d'));
        $this->assertSame('25.00', $batch->mrp);
        $this->assertSame('12.50', $batch->purchase_rate);
        $this->assertSame('22.00', $batch->sale_rate);
        $this->assertSame('12.000000', $batch->available_quantity);
        $this->assertSame('purchase_receive', $movement->movement_type);
        $this->assertSame(PurchaseInvoice::class, $movement->source_type);
        $this->assertSame($invoice->id, $movement->source_id);
        $this->assertSame('12.000000', $movement->quantity);
        $this->assertSame(1, AuditEvent::query()->where('action', 'purchase_invoice.finalized')->count());
    }

    public function test_owner_can_view_edit_cancel_draft_and_search_inventory_batches(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);
        $supplier = $this->supplier($owner);
        $product = $this->product($owner);

        Livewire::test(PurchaseInvoiceForm::class)
            ->set('invoice.supplier_id', (string) $supplier->id)
            ->set('invoice.invoice_number', 'INV-EDIT-001')
            ->set('invoice.invoice_date', '2026-08-12')
            ->set('invoice.received_on', '2026-08-12')
            ->set('items.0.product_id', (string) $product->id)
            ->call('useProduct', 0)
            ->set('items.0.batch_number', 'BATCH-EDIT')
            ->set('items.0.expires_on', '2028-01-31')
            ->set('items.0.quantity', '5')
            ->set('items.0.mrp', '30.00')
            ->set('items.0.purchase_rate', '10.00')
            ->call('save')
            ->assertHasNoErrors();

        $invoice = PurchaseInvoice::query()->firstOrFail();

        $this->get(route('purchase-invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Purchase Invoice Detail')
            ->assertSee('INV-EDIT-001')
            ->assertSee('Edit Invoice');

        $this->get(route('purchase-invoices.edit', $invoice))
            ->assertOk()
            ->assertSee('Edit Purchase Invoice');

        Livewire::test(PurchaseInvoiceForm::class, ['record' => $invoice])
            ->assertSet('purchaseInvoiceId', $invoice->id)
            ->set('items.0.quantity', '6')
            ->set('items.0.purchase_rate', '11.00')
            ->call('save')
            ->assertHasNoErrors();

        $invoice->refresh();
        $this->assertSame('66.00', $invoice->subtotal_amount);
        $this->assertSame(1, AuditEvent::query()->where('action', 'purchase_invoice.updated')->count());

        Livewire::test(PurchaseInvoiceIndex::class)
            ->call('cancelDraft', $invoice->id);

        $invoice->refresh();
        $this->assertSame('cancelled', $invoice->status);
        $this->assertSame(0, ProductBatch::query()->count());
        $this->assertSame(0, StockMovement::query()->count());
        $this->assertSame(1, AuditEvent::query()->where('action', 'purchase_invoice.cancelled')->count());

        ProductBatch::query()->create([
            'product_id' => $product->id,
            'batch_number' => 'SEARCH-BATCH',
            'expires_on' => today()->addDays(45),
            'mrp' => '30.00',
            'purchase_rate' => '11.00',
            'sale_rate' => '25.00',
            'available_quantity' => '6.000000',
            'is_blocked' => false,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        Livewire::test(InventoryBatchIndex::class)
            ->set('search', 'SEARCH-BATCH')
            ->assertSee('SEARCH-BATCH')
            ->set('expiryFilter', 'expiring')
            ->assertSee('SEARCH-BATCH');
    }

    public function test_user_without_inventory_permission_is_forbidden(): void
    {
        app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/inventory/batches')
            ->assertForbidden();
    }

    private function supplier(User $owner): Supplier
    {
        return Supplier::query()->create([
            'name' => 'Receiving Supplier',
            'code' => 'RCV-SUP',
            'payment_terms_days' => 30,
            'opening_balance' => '0.00',
            'outstanding_balance' => '0.00',
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);
    }

    private function product(User $owner): Product
    {
        $taxRate = TaxRate::query()->create([
            'name' => 'Receiving Tax',
            'rate_percent' => '5.00',
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $product = Product::query()->create([
            'name' => 'Receiving Tablet',
            'sku' => 'RCV-PROD',
            'tax_rate_id' => $taxRate->id,
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

        return $product;
    }

    private function purchaseOrder(User $owner, Supplier $supplier): PurchaseOrder
    {
        return PurchaseOrder::query()->create([
            'supplier_id' => $supplier->id,
            'supplier_name_snapshot' => $supplier->name,
            'order_number' => 'PO-RCV-001',
            'ordered_on' => '2026-08-10',
            'status' => 'sent',
            'subtotal_amount' => '0.00',
            'discount_amount' => '0.00',
            'tax_amount' => '0.00',
            'total_amount' => '0.00',
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);
    }
}
