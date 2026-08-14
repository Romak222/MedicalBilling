<?php

namespace Tests\Feature;

use App\Livewire\StockAdjustmentForm;
use App\Models\AuditEvent;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\FirstRunSetup;
use App\Support\GstReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductionFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_and_status_are_separate_protected_pages(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Operations Dashboard')
            ->assertSee("Today's Sales", false)
            ->assertDontSee('Runtime health and resilience');

        $this->get(route('status'))
            ->assertOk()
            ->assertSee('Runtime health and resilience')
            ->assertDontSee("Today's Sales")
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_owner_can_finalize_stock_adjustment_with_signed_movement_and_journal(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $product = Product::query()->create([
            'name' => 'Adjustment Tablet',
            'sku' => 'ADJ-TAB',
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);
        $batch = ProductBatch::query()->create([
            'product_id' => $product->id,
            'batch_number' => 'ADJ-BATCH',
            'expires_on' => '2028-12-31',
            'mrp' => '12.00',
            'purchase_rate' => '5.00',
            'sale_rate' => '8.00',
            'available_quantity' => '10.000000',
            'is_blocked' => false,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $this->actingAs($owner);

        Livewire::test(StockAdjustmentForm::class)
            ->assertSet('items.0.product_batch_id', '')
            ->set('adjustment.reason', 'Cycle count')
            ->set('newBatchId', (string) $batch->id)
            ->set('newCountedQuantity', '8.000000')
            ->call('addLine')
            ->call('save')
            ->assertHasNoErrors();

        $adjustment = StockAdjustment::query()->with('items')->firstOrFail();
        $journal = JournalEntry::query()->where('entry_type', 'stock_adjustment')->with('lines.account')->firstOrFail();

        $this->assertSame('8.000000', $batch->refresh()->available_quantity);
        $this->assertSame('-2.000000', StockMovement::query()->where('movement_type', StockMovement::TYPE_STOCK_ADJUSTMENT)->value('quantity'));
        $this->assertSame($adjustment->id, StockMovement::query()->where('movement_type', StockMovement::TYPE_STOCK_ADJUSTMENT)->value('source_id'));
        $this->assertSame('10.00', $adjustment->items->first()->value_amount);
        $this->assertSame('10.00', $this->lineFor($journal, '5200')->debit);
        $this->assertSame('10.00', $this->lineFor($journal, '1100')->credit);
        $this->assertSame(1, AuditEvent::query()->where('action', 'stock_adjustment.created')->count());

        $this->get(route('inventory.adjustments.show', $adjustment))
            ->assertOk()
            ->assertSee('Immutable inventory correction')
            ->assertSee('ADJ-');
    }

    public function test_gst_report_is_date_bounded_and_exportable(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());

        $this->actingAs($owner)
            ->get(route('reports.gst.index'))
            ->assertOk()
            ->assertSee('GST and Tax Reports');

        $summary = app(GstReportService::class)->summary('2026-08-01', '2026-08-14');

        $this->assertSame('0.00', $summary['tax_summary']['net_tax_payable']);

        $this->get(route('reports.gst.csv', ['from' => '2026-08-01', 'to' => '2026-08-14']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_backup_and_hardware_pages_are_protected(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());

        $this->actingAs($owner)
            ->get(route('backups.index'))
            ->assertOk()
            ->assertSee('Backup history');

        $this->get(route('hardware.index'))
            ->assertOk()
            ->assertSee('Hardware Readiness');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('backups.index'))
            ->assertForbidden();
    }

    public function test_stock_adjustment_requires_sensitive_inventory_permission(): void
    {
        app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('inventory.adjustments.create'))
            ->assertForbidden();
    }

    private function lineFor(JournalEntry $entry, string $accountCode)
    {
        return $entry->lines->first(fn ($line): bool => $line->account->code === $accountCode);
    }
}
