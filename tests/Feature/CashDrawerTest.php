<?php

namespace Tests\Feature;

use App\Livewire\CashDrawerIndex;
use App\Livewire\SalesInvoiceForm;
use App\Livewire\SalesReturnForm;
use App\Models\CashDrawerEntry;
use App\Models\CashDrawerShift;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\TaxRate;
use App\Models\User;
use App\Support\CashDrawerManager;
use App\Support\FirstRunSetup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CashDrawerTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_record_and_close_cash_drawer_with_variance(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);

        $this->get(route('cash-drawer.index'))
            ->assertOk()
            ->assertSee('Cash Drawer');

        Livewire::test(CashDrawerIndex::class)
            ->set('openingFloat', '100.00')
            ->set('openingNotes', 'Morning float')
            ->call('openShift')
            ->assertHasNoErrors();

        $shift = CashDrawerShift::query()->firstOrFail();

        Livewire::test(CashDrawerIndex::class)
            ->set('entryType', CashDrawerEntry::TYPE_CASH_IN)
            ->set('entryAmount', '20.00')
            ->set('entryReason', 'Safe transfer')
            ->call('addEntry')
            ->assertHasNoErrors()
            ->set('entryType', CashDrawerEntry::TYPE_CASH_OUT)
            ->set('entryAmount', '5.00')
            ->set('entryReason', 'Petty cash')
            ->call('addEntry')
            ->assertHasNoErrors()
            ->set('countedClosingCash', '112.00')
            ->set('closingNotes', 'Three rupees short at handover')
            ->call('closeShift')
            ->assertHasNoErrors();

        $shift->refresh();

        $this->assertSame(CashDrawerShift::STATUS_CLOSED, $shift->status);
        $this->assertSame('100.00', $shift->opening_float);
        $this->assertSame('20.00', $shift->cash_in_amount);
        $this->assertSame('5.00', $shift->cash_out_amount);
        $this->assertSame('115.00', $shift->expected_closing_cash);
        $this->assertSame('112.00', $shift->counted_closing_cash);
        $this->assertSame('-3.00', $shift->variance_amount);
        $this->assertSame(2, CashDrawerEntry::query()->count());

        $this->get(route('cash-drawer.show', $shift))
            ->assertOk()
            ->assertSee($shift->shift_number)
            ->assertSee('Three rupees short at handover');
    }

    public function test_cash_sales_and_cash_refunds_are_attached_to_active_shift(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);
        $shift = app(CashDrawerManager::class)->open('100.00', null, $owner);
        $batch = $this->batch($owner);

        Livewire::test(SalesInvoiceForm::class)
            ->set('sale.invoice_number', 'SI-DRAWER-001')
            ->set('sale.payment_method', 'cash')
            ->set('items.0.product_batch_id', (string) $batch->id)
            ->call('useBatch', 0)
            ->set('items.0.quantity', '1')
            ->set('items.0.unit_price', '24.00')
            ->set('sale.paid_amount', '25.20')
            ->call('save')
            ->assertHasNoErrors();

        $invoice = SalesInvoice::query()->where('invoice_number', 'SI-DRAWER-001')->firstOrFail();

        $this->assertSame($shift->id, $invoice->cash_drawer_shift_id);

        Livewire::test(SalesReturnForm::class, ['salesInvoice' => $invoice])
            ->set('items.0.quantity', '1.000000')
            ->set('return.refund_method', 'cash')
            ->call('save')
            ->assertHasNoErrors();

        $salesReturn = SalesReturn::query()->firstOrFail();
        $this->assertSame($shift->id, $salesReturn->cash_drawer_shift_id);

        $totals = app(CashDrawerManager::class)->calculateTotals($shift);
        $this->assertSame('25.20', $totals['cash_sales_amount']);
        $this->assertSame('25.20', $totals['cash_refunds_amount']);
        $this->assertSame('100.00', $totals['expected_closing_cash']);

        $closed = app(CashDrawerManager::class)->close($shift, '100.00', null, $owner);
        $this->assertSame('0.00', $closed->variance_amount);
    }

    public function test_only_one_open_cash_drawer_shift_is_allowed(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);
        $manager = app(CashDrawerManager::class);

        $manager->open('0.00', null, $owner);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $manager->open('10.00', null, $owner);
    }

    public function test_user_without_cash_drawer_permission_is_forbidden(): void
    {
        app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('cash-drawer.index'))
            ->assertForbidden();
    }

    private function batch(User $owner): ProductBatch
    {
        $taxRate = TaxRate::query()->create([
            'name' => 'Sales Tax',
            'rate_percent' => '5.00',
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $product = Product::query()->create([
            'name' => 'Drawer Test Tablet',
            'sku' => 'DRAWER-TEST',
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

        return ProductBatch::query()->create([
            'product_id' => $product->id,
            'batch_number' => 'DRAWER-BATCH',
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
}
