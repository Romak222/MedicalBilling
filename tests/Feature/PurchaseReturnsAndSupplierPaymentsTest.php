<?php

namespace Tests\Feature;

use App\Livewire\PurchaseReturnForm;
use App\Livewire\SupplierPaymentForm;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierLedgerEntry;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Support\AccountingManager;
use App\Support\FirstRunSetup;
use App\Support\PurchaseReturnManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PurchaseReturnsAndSupplierPaymentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_finalize_bounded_purchase_return_and_review_detail(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $supplier = Supplier::query()->create([
            'name' => 'Return Distributor',
            'code' => 'RETURN-SUP',
            'opening_balance' => '110.00',
            'outstanding_balance' => '110.00',
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);
        [$invoice, $batch] = $this->receivedInvoice($owner, $supplier);

        $this->actingAs($owner);
        $this->get(route('purchase-returns.create', $invoice))
            ->assertOk()
            ->assertSee('Finalize Return');

        Livewire::test(PurchaseReturnForm::class, ['purchaseInvoice' => $invoice])
            ->set('return.return_number', 'pvr-test-001')
            ->set('return.reason', 'Damaged outer packaging')
            ->set('items.0.quantity', '2.000000')
            ->call('save')
            ->assertHasNoErrors();

        $purchaseReturn = PurchaseReturn::query()->with('items')->firstOrFail();
        $batch->refresh();
        $supplier->refresh();
        $journal = JournalEntry::query()->where('entry_type', 'purchase_return')->with('lines.account')->firstOrFail();

        $this->assertSame('PVR-TEST-001', $purchaseReturn->return_number);
        $this->assertSame('22.00', $purchaseReturn->total_amount);
        $this->assertSame('2.000000', $purchaseReturn->items->first()->quantity);
        $this->assertSame('8.000000', $batch->available_quantity);
        $this->assertSame('-2.000000', StockMovement::query()->where('movement_type', StockMovement::TYPE_PURCHASE_RETURN)->value('quantity'));
        $this->assertSame('88.00', $supplier->outstanding_balance);
        $this->assertSame('22.00', SupplierLedgerEntry::query()->where('entry_type', 'purchase_return')->value('debit'));
        $this->assertSame('22.00', $this->lineFor($journal, '2000')->debit);
        $this->assertSame('2.00', $this->lineFor($journal, '1200')->credit);
        $this->assertSame('20.00', $this->lineFor($journal, '1100')->credit);

        app(AccountingManager::class)->postPurchaseReturn($purchaseReturn, $owner);
        $this->assertSame(1, JournalEntry::query()->where('entry_type', 'purchase_return')->count());
        $this->assertSame(1, SupplierLedgerEntry::query()->where('entry_type', 'purchase_return')->count());

        $this->actingAs($owner)
            ->get(route('purchase-returns.index'))
            ->assertOk()
            ->assertSee('Supplier return history')
            ->assertSee('PVR-TEST-001');

        $this->get(route('purchase-returns.show', $purchaseReturn))
            ->assertOk()
            ->assertSee('Immutable supplier return')
            ->assertSee('Damaged outer packaging')
            ->assertSee($journal->entry_number);

        $this->get(route('purchase-invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Return Stock');
    }

    public function test_purchase_return_cannot_exceed_current_batch_stock_or_be_repeated_for_same_line(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $supplier = Supplier::query()->create(['name' => 'Bounded Distributor', 'is_active' => true]);
        [$invoice] = $this->receivedInvoice($owner, $supplier);
        $invoiceItem = $invoice->items->firstOrFail();

        app(PurchaseReturnManager::class)->createFinalizedReturn($invoice, [
            'return' => ['return_number' => 'PVR-BOUNDED-001', 'return_date' => '2026-08-14'],
            'items' => [['purchase_invoice_item_id' => $invoiceItem->id, 'quantity' => '2', 'free_quantity' => '0']],
        ], $owner);

        $this->expectException(HttpException::class);
        app(PurchaseReturnManager::class)->createFinalizedReturn($invoice->fresh(), [
            'return' => ['return_number' => 'PVR-BOUNDED-002', 'return_date' => '2026-08-14'],
            'items' => [['purchase_invoice_item_id' => $invoiceItem->id, 'quantity' => '9', 'free_quantity' => '0']],
        ], $owner);
    }

    public function test_owner_can_post_supplier_payment_and_review_payment_detail(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $supplier = Supplier::query()->create([
            'name' => 'Payment Distributor',
            'code' => 'PAY-SUP',
            'opening_balance' => '100.00',
            'outstanding_balance' => '100.00',
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $this->actingAs($owner);
        $this->get(route('supplier-payments.create', $supplier))
            ->assertOk()
            ->assertSee('Post Payment');

        Livewire::test(SupplierPaymentForm::class, ['supplier' => $supplier])
            ->set('paymentDate', '2026-08-14')
            ->set('amount', '40.00')
            ->set('reference', 'UTR-TEST-001')
            ->call('save')
            ->assertHasNoErrors();

        $payment = SupplierPayment::query()->firstOrFail();
        $supplier->refresh();
        $journal = $payment->journalEntry()->with('lines.account')->firstOrFail();

        $this->assertSame('40.00', $payment->amount);
        $this->assertSame('60.00', $supplier->outstanding_balance);
        $this->assertSame('40.00', SupplierLedgerEntry::query()->where('entry_type', 'supplier_payment')->value('debit'));
        $this->assertSame('40.00', $this->lineFor($journal, '2000')->debit);
        $this->assertSame('40.00', $this->lineFor($journal, '1060')->credit);
        $this->assertSame('UTR-TEST-001', $payment->reference);

        $this->actingAs($owner)
            ->get(route('supplier-payments.index', $supplier))
            ->assertOk()
            ->assertSee('Supplier settlement history')
            ->assertSee($payment->payment_number);

        $this->get(route('supplier-payments.show', [$supplier, $payment]))
            ->assertOk()
            ->assertSee('Immutable supplier payment')
            ->assertSee('UTR-TEST-001')
            ->assertSee($journal->entry_number);
    }

    public function test_financial_supplier_workflows_require_accounting_permission(): void
    {
        app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $supplier = Supplier::query()->create(['name' => 'Protected Payment Supplier', 'is_active' => true]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('supplier-payments.index', $supplier))
            ->assertForbidden();
        $this->get(route('supplier-payments.create', $supplier))->assertForbidden();
        $this->get(route('purchase-returns.create', PurchaseInvoice::query()->create([
            'supplier_id' => $supplier->id,
            'supplier_name_snapshot' => $supplier->name,
            'invoice_number' => 'PUR-PROTECTED-001',
            'invoice_date' => '2026-08-14',
            'received_on' => '2026-08-14',
            'status' => PurchaseInvoice::STATUS_DRAFT,
        ])))->assertForbidden();
    }

    /** @return array{0: PurchaseInvoice, 1: ProductBatch} */
    private function receivedInvoice(User $owner, Supplier $supplier): array
    {
        $product = Product::query()->create([
            'name' => 'Return Tablet',
            'sku' => 'RETURN-TAB',
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);
        $batch = ProductBatch::query()->create([
            'product_id' => $product->id,
            'batch_number' => 'RETURN-BATCH',
            'expires_on' => '2028-12-31',
            'mrp' => '20.00',
            'purchase_rate' => '10.00',
            'sale_rate' => '15.00',
            'available_quantity' => '10.000000',
            'is_blocked' => false,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);
        $invoice = PurchaseInvoice::query()->create([
            'supplier_id' => $supplier->id,
            'supplier_name_snapshot' => $supplier->name,
            'invoice_number' => 'PUR-RETURN-001',
            'invoice_date' => '2026-08-14',
            'received_on' => '2026-08-14',
            'status' => PurchaseInvoice::STATUS_FINALIZED,
            'subtotal_amount' => '100.00',
            'discount_amount' => '0.00',
            'tax_amount' => '10.00',
            'total_amount' => '110.00',
            'finalized_at' => now(),
            'finalized_by' => $owner->id,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);
        $invoice->items()->create([
            'product_id' => $product->id,
            'product_batch_id' => $batch->id,
            'product_name_snapshot' => $product->name,
            'unit_name' => 'Tablet',
            'batch_number' => $batch->batch_number,
            'expires_on' => $batch->expires_on,
            'quantity' => '10.000000',
            'free_quantity' => '0.000000',
            'mrp' => '20.00',
            'purchase_rate' => '10.00',
            'sale_rate' => '15.00',
            'discount_amount' => '0.00',
            'tax_rate_percent' => '10.00',
            'line_subtotal' => '100.00',
            'line_tax' => '10.00',
            'line_total' => '110.00',
        ]);

        return [$invoice->load('items.purchaseReturnItems'), $batch];
    }

    private function lineFor(JournalEntry $entry, string $accountCode)
    {
        return $entry->lines->first(fn ($line): bool => $line->account->code === $accountCode);
    }
}
