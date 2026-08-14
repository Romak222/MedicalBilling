<?php

namespace Tests\Feature;

use App\Livewire\SupplierForm;
use App\Livewire\SupplierIndex;
use App\Models\AuditEvent;
use App\Models\ProductBatch;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierContact;
use App\Models\User;
use App\Support\FirstRunSetup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class SupplierManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_setup_redirects_guest_supplier_requests_to_login(): void
    {
        app(FirstRunSetup::class)->complete($this->firstRunPayload());

        $this->get('/suppliers')
            ->assertRedirect(route('login'));
    }

    public function test_owner_can_view_suppliers_workspace(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());

        $this->actingAs($owner)
            ->get('/suppliers')
            ->assertOk()
            ->assertSee('Suppliers')
            ->assertSee('Add New Supplier')
            ->assertSee('href="'.route('suppliers.create').'"', false)
            ->assertSee('Active')
            ->assertSee('Deleted');
    }

    public function test_owner_can_open_full_supplier_create_page(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());

        $this->actingAs($owner)
            ->get('/suppliers/create')
            ->assertOk()
            ->assertSee('Add New Supplier')
            ->assertSee('Supplier Identity')
            ->assertSee('Licence, Location and Notes')
            ->assertSee('Credit Terms and Balances')
            ->assertSee('Supplier Contact Person');
    }

    public function test_owner_can_create_supplier_with_primary_contact_and_balances(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);

        Livewire::test(SupplierForm::class)
            ->set('supplier.name', 'Sample Distributor')
            ->set('supplier.code', 'dist-001')
            ->set('supplier.gstin', 'gstin-pending')
            ->set('supplier.drug_license_number', 'DL-SUP-001')
            ->set('supplier.drug_license_valid_until', '2027-12-31')
            ->set('supplier.address_line_1', 'Warehouse Road')
            ->set('supplier.address_line_2', 'Unit 4')
            ->set('supplier.city', 'Pune')
            ->set('supplier.state', 'Maharashtra')
            ->set('supplier.postal_code', '411001')
            ->set('supplier.phone', '020-400000')
            ->set('supplier.email', 'supplier@example.test')
            ->set('supplier.payment_terms_days', '30')
            ->set('supplier.opening_balance', '1250.50')
            ->set('supplier.credit_limit', '50000.00')
            ->set('supplier.outstanding_balance', '1250.50')
            ->set('supplier.notes', 'Preferred ethical supplier.')
            ->set('contact.name', 'Primary Buyer Desk')
            ->set('contact.role', 'Accounts')
            ->set('contact.phone', '020-400001')
            ->set('contact.email', 'accounts@example.test')
            ->call('save')
            ->assertHasNoErrors();

        $supplier = Supplier::query()->with('primaryContact')->firstOrFail();

        $this->assertSame('Sample Distributor', $supplier->name);
        $this->assertSame('DIST-001', $supplier->code);
        $this->assertSame('GSTIN-PENDING', $supplier->gstin);
        $this->assertSame('1250.50', $supplier->opening_balance);
        $this->assertSame('50000.00', $supplier->credit_limit);
        $this->assertSame('1250.50', $supplier->outstanding_balance);
        $this->assertSame('Primary Buyer Desk', $supplier->primaryContact->name);
        $this->assertSame(1, SupplierContact::query()->count());
        $this->assertSame(1, AuditEvent::query()->where('action', 'supplier.created')->count());
        $this->assertSame(0, PurchaseOrder::query()->count());
        $this->assertSame(0, PurchaseInvoice::query()->count());
        $this->assertSame(0, ProductBatch::query()->count());
        $this->assertSame(0, StockMovement::query()->count());
        $this->assertFalse(Schema::hasTable('supplier_ledger_entries'));
    }

    public function test_owner_can_view_edit_delete_and_restore_supplier(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);

        Livewire::test(SupplierForm::class)
            ->set('supplier.name', 'Original Supplier')
            ->set('supplier.code', 'SUP-EDIT')
            ->set('supplier.opening_balance', '0')
            ->set('supplier.outstanding_balance', '0')
            ->set('contact.name', 'Original Contact')
            ->call('save')
            ->assertHasNoErrors();

        $supplier = Supplier::query()->firstOrFail();

        $this->get(route('suppliers.show', $supplier))
            ->assertOk()
            ->assertSee('Supplier Detail')
            ->assertSee('Original Supplier')
            ->assertSee('Edit Supplier');

        $this->get(route('suppliers.edit', $supplier))
            ->assertOk()
            ->assertSee('Edit Supplier');

        Livewire::test(SupplierForm::class, ['record' => $supplier])
            ->assertSet('supplierId', $supplier->id)
            ->set('supplier.name', 'Updated Supplier')
            ->set('supplier.code', 'SUP-UPDATED')
            ->set('supplier.payment_terms_days', '45')
            ->set('supplier.opening_balance', '100.00')
            ->set('supplier.credit_limit', '10000.00')
            ->set('supplier.outstanding_balance', '250.00')
            ->set('contact.name', 'Updated Contact')
            ->set('contact.phone', '9999999999')
            ->call('save')
            ->assertHasNoErrors();

        $supplier->refresh()->load('primaryContact');
        $this->assertSame('Updated Supplier', $supplier->name);
        $this->assertSame('SUP-UPDATED', $supplier->code);
        $this->assertSame(45, $supplier->payment_terms_days);
        $this->assertSame('250.00', $supplier->outstanding_balance);
        $this->assertSame('Updated Contact', $supplier->primaryContact->name);
        $this->assertSame(1, AuditEvent::query()->where('action', 'supplier.updated')->count());

        $this->get('/suppliers')
            ->assertOk()
            ->assertSee('View')
            ->assertSee('Edit')
            ->assertSee('Delete');

        Livewire::test(SupplierIndex::class)
            ->call('deactivateSupplier', $supplier->id);

        $supplier->refresh();
        $this->assertFalse($supplier->is_active);
        $this->assertSame(1, Supplier::query()->count());
        $this->assertSame(1, AuditEvent::query()->where('action', 'supplier.deactivated')->count());

        Livewire::test(SupplierIndex::class)
            ->call('restoreSupplier', $supplier->id);

        $supplier->refresh();
        $this->assertTrue($supplier->is_active);
        $this->assertSame(1, AuditEvent::query()->where('action', 'supplier.restored')->count());
    }

    public function test_user_without_supplier_permission_is_forbidden(): void
    {
        app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/suppliers')
            ->assertForbidden();
    }
}
