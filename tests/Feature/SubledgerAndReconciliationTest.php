<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Models\JournalEntry;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\Supplier;
use App\Models\SupplierLedgerEntry;
use App\Models\User;
use App\Support\AccountingManager;
use App\Support\FirstRunSetup;
use App\Support\PaymentReconciliationManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SubledgerAndReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_source_documents_post_supplier_and_customer_subledger_entries(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $supplier = Supplier::query()->create([
            'name' => 'Ledger Distributor',
            'code' => 'LEDGER-SUP',
            'opening_balance' => '10.00',
            'outstanding_balance' => '10.00',
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $purchase = PurchaseInvoice::query()->create([
            'supplier_id' => $supplier->id,
            'supplier_name_snapshot' => $supplier->name,
            'invoice_number' => 'PUR-LEDGER-001',
            'invoice_date' => '2026-08-14',
            'received_on' => '2026-08-14',
            'status' => PurchaseInvoice::STATUS_FINALIZED,
            'subtotal_amount' => '100.00',
            'discount_amount' => '0.00',
            'tax_amount' => '8.00',
            'total_amount' => '108.00',
            'finalized_at' => now(),
            'finalized_by' => $owner->id,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        app(AccountingManager::class)->postPurchaseReceipt($purchase, $owner);

        $supplierEntry = SupplierLedgerEntry::query()->firstOrFail();
        $this->assertSame('108.00', $supplierEntry->credit);
        $this->assertSame(PurchaseInvoice::class, $supplierEntry->source_type);
        $this->assertSame($purchase->id, $supplierEntry->source_id);
        $this->assertSame('118.00', $supplier->refresh()->outstanding_balance);

        $customer = Customer::query()->create([
            'name' => 'Ledger Customer',
            'code' => 'LEDGER-CUS',
            'opening_balance' => '50.00',
            'outstanding_balance' => '50.00',
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);
        $sale = SalesInvoice::query()->create([
            'invoice_number' => 'SI-LEDGER-001',
            'invoice_date' => '2026-08-14',
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'status' => SalesInvoice::STATUS_FINALIZED,
            'subtotal_amount' => '25.00',
            'discount_amount' => '0.00',
            'tax_amount' => '0.00',
            'total_amount' => '25.00',
            'payment_method' => 'cash',
            'paid_amount' => '25.00',
            'change_amount' => '0.00',
            'finalized_at' => now(),
            'finalized_by' => $owner->id,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);
        $salesReturn = SalesReturn::query()->create([
            'sales_invoice_id' => $sale->id,
            'return_number' => 'SR-LEDGER-001',
            'return_date' => '2026-08-14',
            'status' => SalesReturn::STATUS_FINALIZED,
            'refund_method' => 'store_credit',
            'subtotal_amount' => '25.00',
            'discount_amount' => '0.00',
            'tax_amount' => '0.00',
            'total_amount' => '25.00',
            'refund_amount' => '25.00',
            'finalized_at' => now(),
            'finalized_by' => $owner->id,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        app(AccountingManager::class)->postSalesReturn($salesReturn, $owner);

        $customerEntry = CustomerLedgerEntry::query()->firstOrFail();
        $this->assertSame('25.00', $customerEntry->credit);
        $this->assertSame(SalesReturn::class, $customerEntry->source_type);
        $this->assertSame($salesReturn->id, $customerEntry->source_id);
        $this->assertSame('25.00', $customer->refresh()->outstanding_balance);

        $this->actingAs($owner)
            ->get(route('suppliers.ledger', $supplier))
            ->assertOk()
            ->assertSee('Supplier ledger history')
            ->assertSee('Purchase receipt PUR-LEDGER-001');

        $this->get(route('customers.ledger', $customer))
            ->assertOk()
            ->assertSee('Customer ledger history')
            ->assertSee('Customer credit for return SR-LEDGER-001');
    }

    public function test_card_settlement_reconciliation_posts_bank_fee_and_receivable_lines(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->cardSale($owner, 'SI-RECON-001', '80.00');
        $this->cardSale($owner, 'SI-RECON-002', '20.00');

        $reconciliation = app(PaymentReconciliationManager::class)->create([
            'payment_method' => 'card',
            'period_from' => '2026-08-14',
            'period_to' => '2026-08-14',
            'settlement_date' => '2026-08-15',
            'settlement_reference' => 'card-settlement-001',
            'settled_amount' => '97.50',
            'fee_amount' => '2.50',
            'notes' => 'Provider settlement batch.',
        ], $owner);

        $entry = $reconciliation->journalEntry->load('lines.account');
        $this->assertSame('reconciled', $reconciliation->status);
        $this->assertSame('100.00', $reconciliation->expected_amount);
        $this->assertSame('97.50', $reconciliation->settled_amount);
        $this->assertSame('2.50', $reconciliation->fee_amount);
        $this->assertSame('payment_reconciliation', $entry->entry_type);
        $this->assertSame('100.00', $this->sumLines($entry, 'debit'));
        $this->assertSame('100.00', $this->sumLines($entry, 'credit'));
        $this->assertSame('97.50', $this->lineFor($entry, '1050')->debit);
        $this->assertSame('2.50', $this->lineFor($entry, '6000')->debit);
        $this->assertSame('100.00', $this->lineFor($entry, '1010')->credit);

        $this->actingAs($owner)
            ->get(route('accounting.reconciliation.index'))
            ->assertOk()
            ->assertSee('Reconcile card and UPI receipts')
            ->assertSee('CARD-SETTLEMENT-001');

        $this->get(route('accounting.reconciliation.show', $reconciliation))
            ->assertOk()
            ->assertSee('Settlement Detail')
            ->assertSee($entry->entry_number);
    }

    public function test_reconciliation_rejects_amount_mismatch_and_duplicate_periods(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->cardSale($owner, 'SI-RECON-003', '100.00');
        $manager = app(PaymentReconciliationManager::class);

        try {
            $manager->create([
                'payment_method' => 'card',
                'period_from' => '2026-08-14',
                'period_to' => '2026-08-14',
                'settlement_date' => '2026-08-15',
                'settlement_reference' => 'card-settlement-bad',
                'settled_amount' => '99.00',
                'fee_amount' => '0.00',
            ], $owner);
            $this->fail('An amount mismatch should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('settled_amount', $exception->errors());
        }

        $manager->create([
            'payment_method' => 'card',
            'period_from' => '2026-08-14',
            'period_to' => '2026-08-14',
            'settlement_date' => '2026-08-15',
            'settlement_reference' => 'card-settlement-good',
            'settled_amount' => '100.00',
            'fee_amount' => '0.00',
        ], $owner);

        $this->expectException(ValidationException::class);
        $manager->create([
            'payment_method' => 'card',
            'period_from' => '2026-08-14',
            'period_to' => '2026-08-14',
            'settlement_date' => '2026-08-16',
            'settlement_reference' => 'card-settlement-duplicate',
            'settled_amount' => '100.00',
            'fee_amount' => '0.00',
        ], $owner);
    }

    public function test_accounting_pages_are_protected_from_users_without_permission(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $supplier = Supplier::query()->create(['name' => 'Protected Supplier', 'is_active' => true]);
        $customer = Customer::query()->create(['name' => 'Protected Customer', 'is_active' => true]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('accounting.reconciliation.index'))
            ->assertForbidden();
        $this->get(route('suppliers.ledger', $supplier))->assertForbidden();
        $this->get(route('customers.ledger', $customer))->assertForbidden();

        $this->assertTrue($owner->hasPermission('accounting.view'));
    }

    private function cardSale(User $owner, string $invoiceNumber, string $total): SalesInvoice
    {
        return SalesInvoice::query()->create([
            'invoice_number' => $invoiceNumber,
            'invoice_date' => '2026-08-14',
            'status' => SalesInvoice::STATUS_FINALIZED,
            'subtotal_amount' => $total,
            'discount_amount' => '0.00',
            'tax_amount' => '0.00',
            'total_amount' => $total,
            'payment_method' => 'card',
            'paid_amount' => $total,
            'change_amount' => '0.00',
            'finalized_at' => now(),
            'finalized_by' => $owner->id,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);
    }

    private function sumLines(JournalEntry $entry, string $column): string
    {
        $cents = $entry->lines->sum(function ($line) use ($column): int {
            [$whole, $fraction] = array_pad(explode('.', (string) $line->{$column}, 2), 2, '');

            return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
        });

        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }

    private function lineFor(JournalEntry $entry, string $accountCode)
    {
        return $entry->lines->first(fn ($line): bool => $line->account->code === $accountCode);
    }
}
