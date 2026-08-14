<?php

namespace Tests\Feature;

use App\Livewire\CatalogueOptionManager;
use App\Livewire\ProductForm;
use App\Livewire\ProductIndex;
use App\Models\AuditEvent;
use App\Models\Category;
use App\Models\DosageFormMaster;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductBatch;
use App\Models\ProductTypeMaster;
use App\Models\ProductUnit;
use App\Models\ScheduleLabelMaster;
use App\Models\StockMovement;
use App\Models\TaxRate;
use App\Models\UnitMaster;
use App\Models\User;
use App\Support\FirstRunSetup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class ProductCatalogueTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_setup_redirects_guest_product_requests_to_login(): void
    {
        app(FirstRunSetup::class)->complete($this->firstRunPayload());

        $this->get('/products')
            ->assertRedirect(route('login'));
    }

    public function test_owner_can_view_products_workspace(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());

        $this->actingAs($owner)
            ->get('/products')
            ->assertOk()
            ->assertSee('Products')
            ->assertSee('Add New Product')
            ->assertSee('href="'.route('products.create').'"', false)
            ->assertSee('Active')
            ->assertSee('Deleted');
    }

    public function test_owner_can_open_full_product_create_page(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());

        $this->actingAs($owner)
            ->get('/products/create')
            ->assertOk()
            ->assertSee('Add New Product')
            ->assertSee('Core Product Details')
            ->assertSee('Category, Manufacturer and Tax')
            ->assertSee('Form, Pack, Regulatory and Unit Details')
            ->assertSee('USB Scan')
            ->assertSee('Scan Barcode');
    }

    public function test_owner_can_create_catalogue_product(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());

        $this->actingAs($owner);

        Livewire::test(ProductForm::class)
            ->set('product.name', 'Sample Tablet')
            ->set('product.sku', 'SKU-001')
            ->set('product.generic_name', 'Sample Generic')
            ->set('product.composition', 'Ingredient details entered by store')
            ->set('product.product_type', 'Medicine')
            ->set('product.form', 'Tablet')
            ->set('product.strength', '500 mg')
            ->set('product.pack_size', '10 tablets')
            ->set('product.hsn_code', '3004')
            ->set('product.schedule_label', 'Store-entered schedule')
            ->set('product.prescription_required', true)
            ->set('product.controlled_medicine', true)
            ->set('manufacturer.name', 'Sample Manufacturer')
            ->set('category.name', 'Sample Category')
            ->set('tax_rate.name', 'Store GST Rate')
            ->set('tax_rate.rate_percent', '12.50')
            ->set('tax_rate.effective_from', '2026-04-01')
            ->set('unit.unit_name', 'Tablet')
            ->set('unit.unit_code', 'TAB')
            ->set('unit.conversion_factor', '1')
            ->set('barcode.barcode', '8900000000012')
            ->set('barcode.barcode_type', 'EAN13')
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::query()->with(['baseUnit', 'taxRate'])->firstOrFail();

        $this->assertSame('Sample Tablet', $product->name);
        $this->assertTrue($product->prescription_required);
        $this->assertTrue($product->controlled_medicine);
        $this->assertSame('1.000000', $product->baseUnit->conversion_factor);
        $this->assertSame('12.50', $product->taxRate->rate_percent);
        $this->assertSame(1, Manufacturer::query()->count());
        $this->assertSame(1, Category::query()->count());
        $this->assertSame(1, TaxRate::query()->count());
        $this->assertSame(1, ProductUnit::query()->count());
        $this->assertSame(1, ProductBarcode::query()->count());
        $this->assertSame(1, AuditEvent::query()->where('action', 'catalogue.product.created')->count());
        $this->assertFalse(Schema::hasColumn('products', 'stock_quantity'));
        $this->assertSame(0, ProductBatch::query()->count());
        $this->assertSame(0, StockMovement::query()->count());
    }

    public function test_owner_can_edit_delete_and_restore_product(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);

        Livewire::test(ProductForm::class)
            ->set('product.name', 'Original Tablet')
            ->set('product.sku', 'SKU-EDIT')
            ->set('unit.unit_name', 'Tablet')
            ->set('unit.unit_code', 'TAB')
            ->set('unit.conversion_factor', '1')
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::query()->firstOrFail();

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Product Detail')
            ->assertSee('Original Tablet')
            ->assertSee('Edit Product');

        $this->get(route('products.edit', $product))
            ->assertOk()
            ->assertSee('Edit Product');

        Livewire::test(ProductForm::class, ['record' => $product])
            ->assertSet('productId', $product->id)
            ->set('product.name', 'Updated Tablet')
            ->set('product.sku', 'SKU-EDIT-2')
            ->set('unit.unit_name', 'Strip')
            ->set('unit.unit_code', 'STRIP')
            ->set('unit.conversion_factor', '10')
            ->call('save')
            ->assertHasNoErrors();

        $product->refresh();
        $this->assertSame('Updated Tablet', $product->name);
        $this->assertSame('SKU-EDIT-2', $product->sku);
        $this->assertSame('STRIP', $product->baseUnit->unit_code);
        $this->assertSame('10.000000', $product->baseUnit->conversion_factor);
        $this->assertSame(1, AuditEvent::query()->where('action', 'catalogue.product.updated')->count());

        Livewire::test(ProductIndex::class)
            ->call('deactivateProduct', $product->id);

        $product->refresh();
        $this->assertFalse($product->is_active);
        $this->assertSame(1, Product::query()->count());
        $this->assertSame(1, AuditEvent::query()->where('action', 'catalogue.product.deactivated')->count());

        Livewire::test(ProductIndex::class)
            ->call('restoreProduct', $product->id);

        $product->refresh();
        $this->assertTrue($product->is_active);
        $this->assertSame(1, AuditEvent::query()->where('action', 'catalogue.product.restored')->count());
    }

    public function test_user_without_catalogue_permission_is_forbidden(): void
    {
        app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/products')
            ->assertForbidden();
    }

    public function test_owner_can_open_product_option_hub_and_detail_pages(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);

        $this->get('/catalogue/masters')
            ->assertOk()
            ->assertSee('Product Options')
            ->assertSee('Manufacturers')
            ->assertSee('Categories')
            ->assertSee('Tax Rates')
            ->assertSee('Units')
            ->assertSee('Product Types')
            ->assertSee('Dosage Forms')
            ->assertSee('Schedule Labels');

        $this->get('/catalogue/masters/manufacturers')
            ->assertOk()
            ->assertSee('Manufacturers')
            ->assertSee('Add Manufacturer')
            ->assertSee('Search manufacturers');

        $this->get('/catalogue/masters/manufacturers/create')
            ->assertOk()
            ->assertSee('Add Manufacturer')
            ->assertSee('Manufacturer Name');
    }

    public function test_owner_can_create_edit_delete_and_restore_product_option_records(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);

        Livewire::test(CatalogueOptionManager::class, ['type' => 'manufacturers'])
            ->set('form.name', 'Reusable Manufacturer')
            ->set('form.code', 'rm')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, Manufacturer::query()->where('name', 'Reusable Manufacturer')->count());
        $this->assertSame('RM', Manufacturer::query()->firstOrFail()->code);
        $this->assertSame(1, AuditEvent::query()->where('action', 'catalogue.manufacturer.created')->count());

        $manufacturer = Manufacturer::query()->firstOrFail();

        $this->get('/catalogue/masters/manufacturers')
            ->assertOk()
            ->assertSee('View')
            ->assertSee('Edit')
            ->assertSee('Delete');

        $this->get(route('catalogue.options.view', ['type' => 'manufacturers', 'record' => $manufacturer]))
            ->assertOk()
            ->assertSee('Manufacturer Detail')
            ->assertSee('Reusable Manufacturer')
            ->assertSee('Edit Manufacturer');

        $this->get(route('catalogue.options.edit', ['type' => 'manufacturers', 'record' => $manufacturer]))
            ->assertOk()
            ->assertSee('Edit Manufacturer');

        Livewire::test(CatalogueOptionManager::class, ['type' => 'manufacturers', 'record' => $manufacturer])
            ->assertSet('recordId', $manufacturer->id)
            ->set('form.name', 'Updated Manufacturer')
            ->set('form.code', 'um')
            ->call('save')
            ->assertHasNoErrors();

        $manufacturer->refresh();
        $this->assertSame('Updated Manufacturer', $manufacturer->name);
        $this->assertSame('UM', $manufacturer->code);
        $this->assertSame(1, AuditEvent::query()->where('action', 'catalogue.manufacturer.updated')->count());

        Livewire::test(CatalogueOptionManager::class, ['type' => 'manufacturers'])
            ->call('deactivate', $manufacturer->id);

        $manufacturer->refresh();
        $this->assertFalse($manufacturer->is_active);
        $this->assertSame(1, AuditEvent::query()->where('action', 'catalogue.manufacturer.deactivated')->count());

        Livewire::test(CatalogueOptionManager::class, ['type' => 'manufacturers'])
            ->call('restore', $manufacturer->id);

        $manufacturer->refresh();
        $this->assertTrue($manufacturer->is_active);
        $this->assertSame(1, AuditEvent::query()->where('action', 'catalogue.manufacturer.restored')->count());
    }

    public function test_owner_can_create_each_product_option_type(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);

        Livewire::test(CatalogueOptionManager::class, ['type' => 'categories'])
            ->set('form.name', 'Reusable Category')
            ->call('save')
            ->assertHasNoErrors();

        Livewire::test(CatalogueOptionManager::class, ['type' => 'tax-rates'])
            ->set('form.name', 'Reusable Tax')
            ->set('form.rate_percent', '5.00')
            ->call('save')
            ->assertHasNoErrors();

        Livewire::test(CatalogueOptionManager::class, ['type' => 'units'])
            ->set('form.name', 'Strip')
            ->set('form.code', 'strip')
            ->call('save')
            ->assertHasNoErrors();

        Livewire::test(CatalogueOptionManager::class, ['type' => 'product-types'])
            ->set('form.name', 'Medicine')
            ->call('save')
            ->assertHasNoErrors();

        Livewire::test(CatalogueOptionManager::class, ['type' => 'dosage-forms'])
            ->set('form.name', 'Tablet')
            ->call('save')
            ->assertHasNoErrors();

        Livewire::test(CatalogueOptionManager::class, ['type' => 'schedule-labels'])
            ->set('form.name', 'Store Schedule')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, Category::query()->where('name', 'Reusable Category')->count());
        $this->assertSame(1, TaxRate::query()->where('name', 'Reusable Tax')->count());
        $this->assertSame(1, UnitMaster::query()->where('code', 'STRIP')->count());
        $this->assertSame(1, ProductTypeMaster::query()->where('name', 'Medicine')->count());
        $this->assertSame(1, DosageFormMaster::query()->where('name', 'Tablet')->count());
        $this->assertSame(1, ScheduleLabelMaster::query()->where('name', 'Store Schedule')->count());
        $this->assertSame(1, AuditEvent::query()->where('action', 'catalogue.unit.created')->count());
        $this->assertSame(1, AuditEvent::query()->where('action', 'catalogue.product_type.created')->count());
    }

    public function test_owner_can_apply_saved_product_option_values(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);

        $productType = ProductTypeMaster::query()->create(['name' => 'Medicine', 'is_active' => true]);
        $dosageForm = DosageFormMaster::query()->create(['name' => 'Tablet', 'is_active' => true]);
        $scheduleLabel = ScheduleLabelMaster::query()->create(['name' => 'Store Schedule', 'is_active' => true]);
        $unit = UnitMaster::query()->create(['name' => 'Strip', 'code' => 'STRIP', 'is_active' => true]);

        Livewire::test(ProductForm::class)
            ->set('selectedProductTypeMasterId', (string) $productType->id)
            ->call('useProductTypeMaster')
            ->assertSet('product.product_type', 'Medicine')
            ->set('selectedDosageFormMasterId', (string) $dosageForm->id)
            ->call('useDosageFormMaster')
            ->assertSet('product.form', 'Tablet')
            ->set('selectedScheduleLabelMasterId', (string) $scheduleLabel->id)
            ->call('useScheduleLabelMaster')
            ->assertSet('product.schedule_label', 'Store Schedule')
            ->set('selectedUnitMasterId', (string) $unit->id)
            ->call('useUnitMaster')
            ->assertSet('unit.unit_name', 'Strip')
            ->assertSet('unit.unit_code', 'STRIP');
    }

    public function test_owner_can_apply_scanned_barcode_value(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);

        Livewire::test(ProductForm::class)
            ->call('applyScannedBarcode', '8900000000012', 'ean_13')
            ->assertSet('barcode.barcode', '8900000000012')
            ->assertSet('barcode.barcode_type', 'EAN-13');
    }
}
