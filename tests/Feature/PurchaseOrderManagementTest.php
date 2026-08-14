<?php

namespace Tests\Feature;

use App\Livewire\PurchaseOrderForm;
use App\Livewire\PurchaseOrderIndex;
use App\Models\AuditEvent;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\TaxRate;
use App\Models\User;
use App\Support\FirstRunSetup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_setup_redirects_guest_purchase_order_requests_to_login(): void
    {
        app(FirstRunSetup::class)->complete($this->firstRunPayload());

        $this->get('/purchases/orders')
            ->assertRedirect(route('login'));
    }

    public function test_owner_can_view_purchase_order_workspace(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());

        $this->actingAs($owner)
            ->get('/purchases/orders')
            ->assertOk()
            ->assertSee('Purchase Orders')
            ->assertSee('Add Purchase Order')
            ->assertSee('href="'.route('purchase-orders.create').'"', false)
            ->assertSee('Draft')
            ->assertSee('Sent')
            ->assertSee('Cancelled');
    }

    public function test_owner_can_open_full_purchase_order_create_page(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);
        $this->supplier($owner);
        $this->product($owner);

        $this->get('/purchases/orders/create')
            ->assertOk()
            ->assertSee('Add Purchase Order')
            ->assertSee('Supplier, Dates and Reference')
            ->assertSee('Products, Quantity and Cost')
            ->assertSee('Add Line');
    }

    public function test_owner_can_create_purchase_order_without_stock_or_invoice_side_effects(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);
        $supplier = $this->supplier($owner);
        $product = $this->product($owner);

        Livewire::test(PurchaseOrderForm::class)
            ->set('order.supplier_id', (string) $supplier->id)
            ->call('useSupplier')
            ->set('order.order_number', 'po-test-001')
            ->set('order.reference_number', 'DIST-QUOTE-1')
            ->set('order.ordered_on', '2026-08-12')
            ->set('order.expected_on', '2026-08-20')
            ->set('order.notes', 'Phase 6 test order.')
            ->set('items.0.product_id', (string) $product->id)
            ->call('useProduct', 0)
            ->set('items.0.quantity', '10')
            ->set('items.0.free_quantity', '1')
            ->set('items.0.unit_cost', '12.50')
            ->set('items.0.discount_amount', '5.00')
            ->set('items.0.notes', 'One free strip.')
            ->call('save')
            ->assertHasNoErrors();

        $order = PurchaseOrder::query()->with('items')->firstOrFail();

        $this->assertSame('PO-TEST-001', $order->order_number);
        $this->assertSame($supplier->id, $order->supplier_id);
        $this->assertSame('draft', $order->status);
        $this->assertSame('125.00', $order->subtotal_amount);
        $this->assertSame('5.00', $order->discount_amount);
        $this->assertSame('6.00', $order->tax_amount);
        $this->assertSame('126.00', $order->total_amount);
        $this->assertSame(1, PurchaseOrderItem::query()->count());
        $this->assertSame('Sample Order Tablet', $order->items->first()->product_name_snapshot);
        $this->assertSame('Tablet', $order->items->first()->unit_name);
        $this->assertSame(1, AuditEvent::query()->where('action', 'purchase_order.created')->count());
        $this->assertSame(0, PurchaseInvoice::query()->count());
        $this->assertSame(0, ProductBatch::query()->count());
        $this->assertSame(0, StockMovement::query()->count());
        $this->assertFalse(Schema::hasTable('supplier_ledger_entries'));
    }

    public function test_owner_can_view_edit_send_cancel_and_reopen_purchase_order(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);
        $supplier = $this->supplier($owner);
        $product = $this->product($owner);

        Livewire::test(PurchaseOrderForm::class)
            ->set('order.supplier_id', (string) $supplier->id)
            ->set('order.order_number', 'PO-EDIT-001')
            ->set('order.ordered_on', '2026-08-12')
            ->set('items.0.product_id', (string) $product->id)
            ->call('useProduct', 0)
            ->set('items.0.quantity', '2')
            ->set('items.0.unit_cost', '10.00')
            ->call('save')
            ->assertHasNoErrors();

        $order = PurchaseOrder::query()->firstOrFail();

        $this->get(route('purchase-orders.show', $order))
            ->assertOk()
            ->assertSee('Purchase Order Detail')
            ->assertSee('PO-EDIT-001')
            ->assertSee('Edit Order');

        $this->get(route('purchase-orders.edit', $order))
            ->assertOk()
            ->assertSee('Edit Purchase Order');

        Livewire::test(PurchaseOrderForm::class, ['record' => $order])
            ->assertSet('purchaseOrderId', $order->id)
            ->set('items.0.quantity', '3')
            ->set('items.0.unit_cost', '15.00')
            ->call('save')
            ->assertHasNoErrors();

        $order->refresh();
        $this->assertSame('45.00', $order->subtotal_amount);
        $this->assertSame('2.25', $order->tax_amount);
        $this->assertSame('47.25', $order->total_amount);
        $this->assertSame(1, AuditEvent::query()->where('action', 'purchase_order.updated')->count());

        $this->get('/purchases/orders')
            ->assertOk()
            ->assertSee('View')
            ->assertSee('Edit')
            ->assertSee('Send')
            ->assertSee('Cancel');

        Livewire::test(PurchaseOrderIndex::class)
            ->call('markSent', $order->id);

        $order->refresh();
        $this->assertSame('sent', $order->status);
        $this->assertSame(1, AuditEvent::query()->where('action', 'purchase_order.sent')->count());

        Livewire::test(PurchaseOrderIndex::class)
            ->call('cancelOrder', $order->id);

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame(1, AuditEvent::query()->where('action', 'purchase_order.cancelled')->count());

        Livewire::test(PurchaseOrderIndex::class)
            ->call('reopenOrder', $order->id);

        $order->refresh();
        $this->assertSame('draft', $order->status);
        $this->assertSame(1, AuditEvent::query()->where('action', 'purchase_order.reopened')->count());
    }

    public function test_user_without_purchase_permission_is_forbidden(): void
    {
        app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/purchases/orders')
            ->assertForbidden();
    }

    private function supplier(User $owner): Supplier
    {
        return Supplier::query()->create([
            'name' => 'Sample Distributor',
            'code' => 'DIST-TEST',
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
            'name' => 'Sample Purchase Tax',
            'rate_percent' => '5.00',
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $product = Product::query()->create([
            'name' => 'Sample Order Tablet',
            'sku' => 'PO-PROD-001',
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
}
