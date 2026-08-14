<?php

namespace Tests\Feature;

use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\User;
use App\Support\AccountingManager;
use App\Support\FirstRunSetup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_review_balanced_journal_entries_and_account_activity(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);
        $invoice = $this->salesInvoice($owner);

        $saleEntry = app(AccountingManager::class)->postSale($invoice, $owner);

        $this->assertSame('sale', $saleEntry->entry_type);
        $this->assertSame(40.0, $saleEntry->lines->sum(fn ($line): string => $line->debit));
        $this->assertSame(40.0, $saleEntry->lines->sum(fn ($line): string => $line->credit));
        $this->assertSame($saleEntry->id, app(AccountingManager::class)->postSale($invoice, $owner)->id);
        $this->assertSame('2026-08-14', $saleEntry->entry_date->toDateString());
        $this->assertSame('posted', $saleEntry->status);
        $this->assertTrue(JournalEntry::query()->whereKey($saleEntry->id)->exists());
        $this->assertSame(1, JournalEntry::query()->where('status', 'posted')->whereDate('entry_date', '>=', '2026-08-01')->whereDate('entry_date', '<=', '2026-08-14')->count());

        $this->get(route('accounting.index'))
            ->assertOk()
            ->assertSee('Posted Ledger')
            ->assertSee('Sales Revenue')
            ->assertSee($saleEntry->entry_number);

        $this->get(route('accounting.journal.show', $saleEntry))
            ->assertOk()
            ->assertSee('Double-entry lines')
            ->assertSee('Inventory Asset');

        $salesReturn = SalesReturn::query()->create([
            'sales_invoice_id' => $invoice->id,
            'return_number' => 'SR-ACCOUNTING-001',
            'return_date' => '2026-08-14',
            'status' => SalesReturn::STATUS_FINALIZED,
            'refund_method' => 'cash',
            'subtotal_amount' => '30.00',
            'discount_amount' => '0.00',
            'tax_amount' => '0.00',
            'total_amount' => '30.00',
            'refund_amount' => '30.00',
            'finalized_at' => now(),
            'finalized_by' => $owner->id,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $returnEntry = app(AccountingManager::class)->postSalesReturn($salesReturn, $owner);
        $this->assertSame('sales_return', $returnEntry->entry_type);
        $this->assertSame(30.0, $returnEntry->lines->sum(fn ($line): string => $line->debit));
        $this->assertSame(30.0, $returnEntry->lines->sum(fn ($line): string => $line->credit));
    }

    public function test_sales_cancellation_is_recorded_as_a_reversal_and_purchase_receipt_posts(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $invoice = $this->salesInvoice($owner);
        $manager = app(AccountingManager::class);

        $sale = $manager->postSale($invoice, $owner);
        $reversal = $manager->reverseSale($invoice, $owner);

        $this->assertSame('sale_reversal', $reversal->entry_type);
        $this->assertSame($sale->id, $reversal->reversal_of_id);
        $this->assertSame(40.0, $reversal->lines->sum(fn ($line): string => $line->debit));
        $this->assertSame(40.0, $reversal->lines->sum(fn ($line): string => $line->credit));

        $purchase = PurchaseInvoice::query()->create([
            'supplier_name_snapshot' => 'Accounting Supplier',
            'invoice_number' => 'PUR-ACCOUNTING-001',
            'invoice_date' => '2026-08-14',
            'received_on' => '2026-08-14',
            'status' => PurchaseInvoice::STATUS_FINALIZED,
            'subtotal_amount' => '100.00',
            'discount_amount' => '5.00',
            'tax_amount' => '9.50',
            'total_amount' => '104.50',
            'finalized_at' => now(),
            'finalized_by' => $owner->id,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $receipt = $manager->postPurchaseReceipt($purchase, $owner);

        $this->assertSame('purchase_receipt', $receipt->entry_type);
        $this->assertSame(104.5, $receipt->lines->sum(fn ($line): string => $line->debit));
        $this->assertSame(104.5, $receipt->lines->sum(fn ($line): string => $line->credit));
    }

    public function test_user_without_accounting_permission_is_forbidden(): void
    {
        app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('accounting.index'))
            ->assertForbidden();
    }

    private function salesInvoice(User $owner): SalesInvoice
    {
        $product = Product::query()->create([
            'name' => 'Accounting Tablet',
            'sku' => 'ACCOUNTING-TAB',
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $batch = ProductBatch::query()->create([
            'product_id' => $product->id,
            'batch_number' => 'ACCOUNTING-BATCH',
            'expires_on' => '2028-12-31',
            'mrp' => '35.00',
            'purchase_rate' => '10.00',
            'sale_rate' => '30.00',
            'available_quantity' => '10.000000',
            'is_blocked' => false,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $invoice = SalesInvoice::query()->create([
            'invoice_number' => 'SI-ACCOUNTING-001',
            'invoice_date' => '2026-08-14',
            'status' => SalesInvoice::STATUS_FINALIZED,
            'subtotal_amount' => '30.00',
            'discount_amount' => '0.00',
            'tax_amount' => '0.00',
            'total_amount' => '30.00',
            'payment_method' => 'cash',
            'paid_amount' => '30.00',
            'change_amount' => '0.00',
            'finalized_at' => now(),
            'finalized_by' => $owner->id,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $invoice->items()->create([
            'product_id' => $product->id,
            'product_batch_id' => $batch->id,
            'product_name_snapshot' => $product->name,
            'batch_number_snapshot' => $batch->batch_number,
            'expires_on_snapshot' => $batch->expires_on,
            'unit_name' => 'Tablet',
            'quantity' => '1.000000',
            'unit_price' => '30.00',
            'discount_amount' => '0.00',
            'tax_rate_percent' => '0.00',
            'line_subtotal' => '30.00',
            'line_tax' => '0.00',
            'line_total' => '30.00',
        ]);

        return $invoice->load('items.productBatch');
    }
}
