<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Models\JournalEntry;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\SalesReturn;
use App\Models\Supplier;
use App\Models\SupplierLedgerEntry;
use App\Models\SupplierPayment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SubledgerManager
{
    public function postPurchaseReceipt(PurchaseInvoice $invoice, JournalEntry $journalEntry, User $actor): ?SupplierLedgerEntry
    {
        $invoice->loadMissing('supplier');

        if (! $invoice->supplier_id || ! $invoice->supplier) {
            return null;
        }

        return DB::transaction(function () use ($invoice, $journalEntry, $actor): SupplierLedgerEntry {
            $existing = SupplierLedgerEntry::query()
                ->where('source_type', $invoice::class)
                ->where('source_id', $invoice->getKey())
                ->where('entry_type', 'purchase_receipt')
                ->first();

            if ($existing) {
                return $existing;
            }

            $entry = SupplierLedgerEntry::query()->create([
                'supplier_id' => $invoice->supplier_id,
                'entry_date' => $invoice->received_on?->toDateString() ?: today()->toDateString(),
                'entry_type' => 'purchase_receipt',
                'status' => 'posted',
                'source_type' => $invoice::class,
                'source_id' => $invoice->getKey(),
                'debit' => '0.00',
                'credit' => $invoice->total_amount,
                'description' => 'Purchase receipt '.$invoice->invoice_number,
                'created_by' => $actor->id,
            ]);

            $invoice->supplier->update([
                'outstanding_balance' => $this->formatCents(
                    $this->cents($invoice->supplier->outstanding_balance) + $this->cents($invoice->total_amount)
                ),
                'updated_by' => $actor->id,
            ]);

            app(AuditLogger::class)->record('supplier_ledger.posted', $actor, $entry, [
                'supplier_id' => $entry->supplier_id,
                'source_type' => $entry->source_type,
                'source_id' => $entry->source_id,
                'journal_entry_id' => $journalEntry->id,
                'credit' => $entry->credit,
            ]);

            return $entry;
        });
    }

    public function postSalesReturn(SalesReturn $salesReturn, User $actor): ?CustomerLedgerEntry
    {
        $salesReturn->loadMissing('salesInvoice.customer');
        $customer = $salesReturn->salesInvoice?->customer;

        if (! $customer) {
            return null;
        }

        $totalCents = $this->cents($salesReturn->total_amount);
        $refundCents = $this->cents($salesReturn->refund_amount);
        $creditCents = strtolower((string) $salesReturn->refund_method) === 'store_credit'
            ? $refundCents
            : max(0, $totalCents - $refundCents);

        if ($creditCents <= 0) {
            return null;
        }

        return DB::transaction(function () use ($salesReturn, $actor, $customer, $creditCents): CustomerLedgerEntry {
            $existing = CustomerLedgerEntry::query()
                ->where('source_type', $salesReturn::class)
                ->where('source_id', $salesReturn->getKey())
                ->where('entry_type', 'sales_return_credit')
                ->first();

            if ($existing) {
                return $existing;
            }

            $entry = CustomerLedgerEntry::query()->create([
                'customer_id' => $customer->id,
                'entry_date' => $salesReturn->return_date?->toDateString() ?: today()->toDateString(),
                'entry_type' => 'sales_return_credit',
                'status' => 'posted',
                'source_type' => $salesReturn::class,
                'source_id' => $salesReturn->getKey(),
                'debit' => '0.00',
                'credit' => $this->formatCents($creditCents),
                'description' => 'Customer credit for return '.$salesReturn->return_number,
                'created_by' => $actor->id,
            ]);

            $customer->update([
                'outstanding_balance' => $this->formatCents(
                    $this->cents($customer->outstanding_balance) - $creditCents
                ),
                'updated_by' => $actor->id,
            ]);

            app(AuditLogger::class)->record('customer_ledger.posted', $actor, $entry, [
                'customer_id' => $entry->customer_id,
                'source_type' => $entry->source_type,
                'source_id' => $entry->source_id,
                'credit' => $entry->credit,
            ]);

            return $entry;
        });
    }

    public function postPurchaseReturn(PurchaseReturn $purchaseReturn, JournalEntry $journalEntry, User $actor): ?SupplierLedgerEntry
    {
        $purchaseReturn->loadMissing('supplier');

        if (! $purchaseReturn->supplier_id || ! $purchaseReturn->supplier) {
            return null;
        }

        return DB::transaction(function () use ($purchaseReturn, $journalEntry, $actor): SupplierLedgerEntry {
            $existing = SupplierLedgerEntry::query()
                ->where('source_type', $purchaseReturn::class)
                ->where('source_id', $purchaseReturn->getKey())
                ->where('entry_type', 'purchase_return')
                ->first();

            if ($existing) {
                return $existing;
            }

            $entry = SupplierLedgerEntry::query()->create([
                'supplier_id' => $purchaseReturn->supplier_id,
                'entry_date' => $purchaseReturn->return_date?->toDateString() ?: today()->toDateString(),
                'entry_type' => 'purchase_return',
                'status' => 'posted',
                'source_type' => $purchaseReturn::class,
                'source_id' => $purchaseReturn->getKey(),
                'debit' => $purchaseReturn->total_amount,
                'credit' => '0.00',
                'description' => 'Supplier credit for return '.$purchaseReturn->return_number,
                'created_by' => $actor->id,
            ]);

            $purchaseReturn->supplier->update([
                'outstanding_balance' => $this->formatCents(
                    $this->cents($purchaseReturn->supplier->outstanding_balance) - $this->cents($purchaseReturn->total_amount)
                ),
                'updated_by' => $actor->id,
            ]);

            app(AuditLogger::class)->record('supplier_ledger.posted', $actor, $entry, [
                'supplier_id' => $entry->supplier_id,
                'source_type' => $entry->source_type,
                'source_id' => $entry->source_id,
                'journal_entry_id' => $journalEntry->id,
                'debit' => $entry->debit,
            ]);

            return $entry;
        });
    }

    public function postSupplierPayment(SupplierPayment $payment, JournalEntry $journalEntry, User $actor): ?SupplierLedgerEntry
    {
        $payment->loadMissing('supplier');

        if (! $payment->supplier_id || ! $payment->supplier) {
            return null;
        }

        return DB::transaction(function () use ($payment, $journalEntry, $actor): SupplierLedgerEntry {
            $existing = SupplierLedgerEntry::query()
                ->where('source_type', $payment::class)
                ->where('source_id', $payment->getKey())
                ->where('entry_type', 'supplier_payment')
                ->first();

            if ($existing) {
                return $existing;
            }

            $entry = SupplierLedgerEntry::query()->create([
                'supplier_id' => $payment->supplier_id,
                'entry_date' => $payment->payment_date?->toDateString() ?: today()->toDateString(),
                'entry_type' => 'supplier_payment',
                'status' => 'posted',
                'source_type' => $payment::class,
                'source_id' => $payment->getKey(),
                'debit' => $payment->amount,
                'credit' => '0.00',
                'description' => 'Supplier payment '.$payment->payment_number,
                'created_by' => $actor->id,
            ]);

            $payment->supplier->update([
                'outstanding_balance' => $this->formatCents(
                    $this->cents($payment->supplier->outstanding_balance) - $this->cents($payment->amount)
                ),
                'updated_by' => $actor->id,
            ]);

            app(AuditLogger::class)->record('supplier_ledger.posted', $actor, $entry, [
                'supplier_id' => $entry->supplier_id,
                'source_type' => $entry->source_type,
                'source_id' => $entry->source_id,
                'journal_entry_id' => $journalEntry->id,
                'debit' => $entry->debit,
            ]);

            return $entry;
        });
    }

    /**
     * @return array{entries: Collection<int, SupplierLedgerEntry>, debit_total: string, credit_total: string, balance: string}
     */
    public function supplierStatement(Supplier $supplier): array
    {
        $entries = SupplierLedgerEntry::query()
            ->where('supplier_id', $supplier->id)
            ->where('status', 'posted')
            ->latest('entry_date')
            ->latest('id')
            ->limit(100)
            ->get();

        return [
            'entries' => $entries,
            'debit_total' => $this->formatCents($this->sumCents($entries, 'debit')),
            'credit_total' => $this->formatCents($this->sumCents($entries, 'credit')),
            'balance' => $this->formatCents(
                $this->cents($supplier->opening_balance) + $this->sumCents($entries, 'credit') - $this->sumCents($entries, 'debit')
            ),
        ];
    }

    /**
     * @return array{entries: Collection<int, CustomerLedgerEntry>, debit_total: string, credit_total: string, balance: string}
     */
    public function customerStatement(Customer $customer): array
    {
        $entries = CustomerLedgerEntry::query()
            ->where('customer_id', $customer->id)
            ->where('status', 'posted')
            ->latest('entry_date')
            ->latest('id')
            ->limit(100)
            ->get();

        return [
            'entries' => $entries,
            'debit_total' => $this->formatCents($this->sumCents($entries, 'debit')),
            'credit_total' => $this->formatCents($this->sumCents($entries, 'credit')),
            'balance' => $this->formatCents(
                $this->cents($customer->opening_balance) + $this->sumCents($entries, 'debit') - $this->sumCents($entries, 'credit')
            ),
        ];
    }

    private function sumCents(Collection $entries, string $column): int
    {
        return $entries->sum(fn ($entry): int => $this->cents($entry->{$column}));
    }

    private function cents(mixed $value): int
    {
        return $this->decimalToScaleInt($value, 2);
    }

    private function decimalToScaleInt(mixed $value, int $scale): int
    {
        $value = (string) ($value ?? '0');
        $sign = str_starts_with($value, '-') ? -1 : 1;
        $value = ltrim($value, '+-');
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = str_pad(substr($fraction, 0, $scale), $scale, '0');

        return $sign * (((int) $whole * (10 ** $scale)) + (int) $fraction);
    }

    private function formatCents(int $value): string
    {
        $sign = $value < 0 ? '-' : '';
        $value = abs($value);

        return sprintf('%s%d.%02d', $sign, intdiv($value, 100), $value % 100);
    }
}
