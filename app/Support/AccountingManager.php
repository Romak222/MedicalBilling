<?php

namespace App\Support;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\PaymentReconciliation;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\StockAdjustment;
use App\Models\SupplierPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AccountingManager
{
    public function postSale(SalesInvoice $invoice, User $actor): JournalEntry
    {
        return DB::transaction(function () use ($invoice, $actor): JournalEntry {
            $invoice->loadMissing('items.productBatch');

            $lines = [];
            $this->addLine($lines, $this->paymentAccountCode($invoice->payment_method), $this->cents($invoice->total_amount), 0, 'Payment received for '.$invoice->invoice_number);
            $this->addLine($lines, '4000', 0, $this->cents($invoice->subtotal_amount) - $this->cents($invoice->discount_amount), 'Net sales revenue for '.$invoice->invoice_number);
            $this->addLine($lines, '2100', 0, $this->cents($invoice->tax_amount), 'Output tax for '.$invoice->invoice_number);

            $costCents = 0;
            foreach ($invoice->items as $item) {
                $costCents += $this->quantityAtCost($item->quantity, $item->productBatch?->purchase_rate);
            }

            $this->addLine($lines, '5000', $costCents, 0, 'Cost of goods sold for '.$invoice->invoice_number);
            $this->addLine($lines, '1100', 0, $costCents, 'Inventory issued for '.$invoice->invoice_number);

            return $this->postEntry(
                $invoice,
                'sale',
                $invoice->invoice_date?->toDateString() ?: today()->toDateString(),
                'Sale '.$invoice->invoice_number,
                $lines,
                $actor
            );
        });
    }

    public function reverseSale(SalesInvoice $invoice, User $actor): JournalEntry
    {
        return DB::transaction(function () use ($invoice, $actor): JournalEntry {
            $original = JournalEntry::query()
                ->with('lines')
                ->where('source_type', $invoice::class)
                ->where('source_id', $invoice->getKey())
                ->where('entry_type', 'sale')
                ->first();

            if (! $original) {
                $this->postSale($invoice, $actor);
                $original = JournalEntry::query()
                    ->where('source_type', $invoice::class)
                    ->where('source_id', $invoice->getKey())
                    ->where('entry_type', 'sale')
                    ->firstOrFail();
            }

            $original->load('lines.account');

            $lines = collect($original->lines)
                ->map(fn ($line): array => [
                    'account_code' => $line->account->code,
                    'debit_cents' => $this->cents($line->credit),
                    'credit_cents' => $this->cents($line->debit),
                    'memo' => 'Reversal of '.$original->entry_number,
                ])
                ->all();

            return $this->postEntry(
                $invoice,
                'sale_reversal',
                today()->toDateString(),
                'Reversal of sale '.$invoice->invoice_number,
                $lines,
                $actor,
                $original
            );
        });
    }

    public function postSalesReturn(SalesReturn $salesReturn, User $actor): JournalEntry
    {
        return DB::transaction(function () use ($salesReturn, $actor): JournalEntry {
            $salesReturn->loadMissing('items.productBatch');

            $lines = [];
            $returnValueCents = $this->cents($salesReturn->total_amount);
            $refundCents = $this->cents($salesReturn->refund_amount);

            $this->addLine($lines, '4000', $this->cents($salesReturn->subtotal_amount) - $this->cents($salesReturn->discount_amount), 0, 'Sales return '.$salesReturn->return_number);
            $this->addLine($lines, '2100', $this->cents($salesReturn->tax_amount), 0, 'Output tax reversed for '.$salesReturn->return_number);
            $this->addLine($lines, $this->paymentAccountCode($salesReturn->refund_method), 0, $refundCents, 'Refund paid for '.$salesReturn->return_number);
            $this->addLine($lines, '2020', 0, max(0, $returnValueCents - $refundCents), 'Customer credit remaining for '.$salesReturn->return_number);

            $restockCostCents = 0;
            foreach ($salesReturn->items as $item) {
                if ($item->restock_to_inventory) {
                    $restockCostCents += $this->quantityAtCost($item->quantity, $item->productBatch?->purchase_rate);
                }
            }

            $this->addLine($lines, '1100', $restockCostCents, 0, 'Inventory restocked for '.$salesReturn->return_number);
            $this->addLine($lines, '5000', 0, $restockCostCents, 'Cost reversed for '.$salesReturn->return_number);

            $entry = $this->postEntry(
                $salesReturn,
                'sales_return',
                $salesReturn->return_date?->toDateString() ?: today()->toDateString(),
                'Sales return '.$salesReturn->return_number,
                $lines,
                $actor
            );

            app(SubledgerManager::class)->postSalesReturn($salesReturn, $actor);

            return $entry;
        });
    }

    public function postPurchaseReceipt(PurchaseInvoice $invoice, User $actor): JournalEntry
    {
        return DB::transaction(function () use ($invoice, $actor): JournalEntry {
            $inventoryCents = $this->cents($invoice->subtotal_amount) - $this->cents($invoice->discount_amount);

            $entry = $this->postEntry(
                $invoice,
                'purchase_receipt',
                $invoice->received_on?->toDateString() ?: today()->toDateString(),
                'Purchase receipt '.$invoice->invoice_number,
                [
                    [
                        'account_code' => '1100',
                        'debit_cents' => $inventoryCents,
                        'credit_cents' => 0,
                        'memo' => 'Inventory received for '.$invoice->invoice_number,
                    ],
                    [
                        'account_code' => '1200',
                        'debit_cents' => $this->cents($invoice->tax_amount),
                        'credit_cents' => 0,
                        'memo' => 'Input tax for '.$invoice->invoice_number,
                    ],
                    [
                        'account_code' => '2000',
                        'debit_cents' => 0,
                        'credit_cents' => $this->cents($invoice->total_amount),
                        'memo' => 'Supplier payable for '.$invoice->invoice_number,
                    ],
                ],
                $actor
            );

            app(SubledgerManager::class)->postPurchaseReceipt($invoice, $entry, $actor);

            return $entry;
        });
    }

    public function postPaymentReconciliation(PaymentReconciliation $reconciliation, User $actor): JournalEntry
    {
        return DB::transaction(function () use ($reconciliation, $actor): JournalEntry {
            $this->validateReconciliationAmounts($reconciliation);

            $entry = $this->postEntry(
                $reconciliation,
                'payment_reconciliation',
                $reconciliation->settlement_date?->toDateString() ?: today()->toDateString(),
                'Payment settlement '.$reconciliation->reconciliation_number,
                [
                    [
                        'account_code' => '1050',
                        'debit_cents' => $this->cents($reconciliation->settled_amount),
                        'credit_cents' => 0,
                        'memo' => 'Settlement received '.$reconciliation->settlement_reference,
                    ],
                    [
                        'account_code' => '6000',
                        'debit_cents' => $this->cents($reconciliation->fee_amount),
                        'credit_cents' => 0,
                        'memo' => 'Provider fee '.$reconciliation->settlement_reference,
                    ],
                    [
                        'account_code' => $this->paymentAccountCode($reconciliation->payment_method),
                        'debit_cents' => 0,
                        'credit_cents' => $this->cents($reconciliation->expected_amount),
                        'memo' => 'Receivable cleared '.$reconciliation->settlement_reference,
                    ],
                ],
                $actor
            );

            $reconciliation->update([
                'status' => 'reconciled',
                'journal_entry_id' => $entry->id,
                'reconciled_by' => $actor->id,
            ]);

            return $entry;
        });
    }

    public function postPurchaseReturn(PurchaseReturn $purchaseReturn, User $actor): JournalEntry
    {
        return DB::transaction(function () use ($purchaseReturn, $actor): JournalEntry {
            $inventoryCents = $this->cents($purchaseReturn->subtotal_amount) - $this->cents($purchaseReturn->discount_amount);

            $entry = $this->postEntry(
                $purchaseReturn,
                'purchase_return',
                $purchaseReturn->return_date?->toDateString() ?: today()->toDateString(),
                'Purchase return '.$purchaseReturn->return_number,
                [
                    [
                        'account_code' => '2000',
                        'debit_cents' => $this->cents($purchaseReturn->total_amount),
                        'credit_cents' => 0,
                        'memo' => 'Supplier payable reduced for '.$purchaseReturn->return_number,
                    ],
                    [
                        'account_code' => '1200',
                        'debit_cents' => 0,
                        'credit_cents' => $this->cents($purchaseReturn->tax_amount),
                        'memo' => 'Input tax reversed for '.$purchaseReturn->return_number,
                    ],
                    [
                        'account_code' => '1100',
                        'debit_cents' => 0,
                        'credit_cents' => $inventoryCents,
                        'memo' => 'Inventory returned to supplier for '.$purchaseReturn->return_number,
                    ],
                ],
                $actor
            );

            $purchaseReturn->update(['journal_entry_id' => $entry->id]);
            app(SubledgerManager::class)->postPurchaseReturn($purchaseReturn, $entry, $actor);

            return $entry;
        });
    }

    public function postSupplierPayment(SupplierPayment $payment, User $actor): JournalEntry
    {
        return DB::transaction(function () use ($payment, $actor): JournalEntry {
            $entry = $this->postEntry(
                $payment,
                'supplier_payment',
                $payment->payment_date?->toDateString() ?: today()->toDateString(),
                'Supplier payment '.$payment->payment_number,
                [
                    [
                        'account_code' => '2000',
                        'debit_cents' => $this->cents($payment->amount),
                        'credit_cents' => 0,
                        'memo' => 'Supplier payable settled for '.$payment->payment_number,
                    ],
                    [
                        'account_code' => $this->supplierPaymentAccountCode($payment->payment_method),
                        'debit_cents' => 0,
                        'credit_cents' => $this->cents($payment->amount),
                        'memo' => 'Payment issued for '.$payment->payment_number,
                    ],
                ],
                $actor
            );

            $payment->update([
                'journal_entry_id' => $entry->id,
                'paid_by' => $actor->id,
            ]);
            app(SubledgerManager::class)->postSupplierPayment($payment, $entry, $actor);

            return $entry;
        });
    }

    public function postStockAdjustment(StockAdjustment $adjustment, User $actor): JournalEntry
    {
        return DB::transaction(function () use ($adjustment, $actor): JournalEntry {
            $lines = [];

            foreach ($adjustment->items as $item) {
                $valueCents = $this->cents($item->value_amount);

                if ($valueCents <= 0) {
                    continue;
                }

                $deltaMicros = $this->decimalToScaleInt($item->delta_quantity, 6);
                $isIncrease = $deltaMicros > 0;
                $lines[] = [
                    'account_code' => $isIncrease ? '1100' : '5200',
                    'debit_cents' => $valueCents,
                    'credit_cents' => 0,
                    'memo' => 'Stock count adjustment '.$adjustment->adjustment_number.' / '.$item->batch_number_snapshot,
                ];
                $lines[] = [
                    'account_code' => $isIncrease ? '5200' : '1100',
                    'debit_cents' => 0,
                    'credit_cents' => $valueCents,
                    'memo' => 'Stock count adjustment '.$adjustment->adjustment_number.' / '.$item->batch_number_snapshot,
                ];
            }

            $entry = $this->postEntry(
                $adjustment,
                'stock_adjustment',
                $adjustment->adjustment_date?->toDateString() ?: today()->toDateString(),
                'Stock adjustment '.$adjustment->adjustment_number,
                $lines,
                $actor
            );

            $adjustment->update(['journal_entry_id' => $entry->id]);

            return $entry;
        });
    }

    /**
     * @param  array<int, array{account_code: string, debit_cents: int, credit_cents: int, memo: string|null}>  $lines
     */
    private function postEntry(
        Model $source,
        string $entryType,
        string $entryDate,
        string $description,
        array $lines,
        User $actor,
        ?JournalEntry $reversalOf = null
    ): JournalEntry {
        $existing = JournalEntry::query()
            ->where('source_type', $source::class)
            ->where('source_id', $source->getKey())
            ->where('entry_type', $entryType)
            ->with('lines.account')
            ->first();

        if ($existing) {
            return $existing;
        }

        $debitCents = 0;
        $creditCents = 0;
        $resolvedLines = [];

        foreach ($lines as $line) {
            $debit = max(0, $line['debit_cents']);
            $credit = max(0, $line['credit_cents']);

            if ($debit === 0 && $credit === 0) {
                continue;
            }

            if ($debit > 0 && $credit > 0) {
                throw new RuntimeException('Accounting lines cannot contain both a debit and a credit.');
            }

            $account = Account::query()->where('code', $line['account_code'])->where('is_active', true)->first();

            if (! $account) {
                throw new RuntimeException('Accounting account '.$line['account_code'].' is not configured.');
            }

            $debitCents += $debit;
            $creditCents += $credit;
            $resolvedLines[] = [
                'account' => $account,
                'debit_cents' => $debit,
                'credit_cents' => $credit,
                'memo' => $line['memo'],
            ];
        }

        if ($resolvedLines === [] || $debitCents !== $creditCents) {
            throw new RuntimeException('Accounting journal entry is not balanced.');
        }

        $entry = JournalEntry::query()->create([
            'entry_number' => $this->nextEntryNumber($entryDate),
            'entry_date' => $entryDate,
            'entry_type' => $entryType,
            'status' => JournalEntry::STATUS_POSTED,
            'source_type' => $source::class,
            'source_id' => $source->getKey(),
            'reversal_of_id' => $reversalOf?->getKey(),
            'description' => $description,
            'created_by' => $actor->id,
        ]);

        foreach ($resolvedLines as $line) {
            $entry->lines()->create([
                'account_id' => $line['account']->id,
                'debit' => $this->formatCents($line['debit_cents']),
                'credit' => $this->formatCents($line['credit_cents']),
                'memo' => $line['memo'],
            ]);
        }

        app(AuditLogger::class)->record('accounting.journal_posted', $actor, $entry, [
            'entry_number' => $entry->entry_number,
            'entry_type' => $entry->entry_type,
            'source_type' => $entry->source_type,
            'source_id' => $entry->source_id,
            'debit' => $this->formatCents($debitCents),
            'credit' => $this->formatCents($creditCents),
        ]);

        return $entry->load('lines.account');
    }

    /**
     * @param  array<int, array{account_code: string, debit_cents: int, credit_cents: int, memo: string|null}>  $lines
     */
    private function addLine(array &$lines, string $accountCode, int $debitCents, int $creditCents, ?string $memo): void
    {
        $lines[] = [
            'account_code' => $accountCode,
            'debit_cents' => $debitCents,
            'credit_cents' => $creditCents,
            'memo' => $memo,
        ];
    }

    private function paymentAccountCode(?string $method): string
    {
        $method = strtolower(trim((string) $method));

        return config('accounting.payment_account_codes.'.$method, config('accounting.payment_account_codes.mixed'));
    }

    private function supplierPaymentAccountCode(?string $method): string
    {
        $method = strtolower(trim((string) $method));

        return config('accounting.supplier_payment_account_codes.'.$method, config('accounting.supplier_payment_account_codes.other'));
    }

    private function validateReconciliationAmounts(PaymentReconciliation $reconciliation): void
    {
        if ($this->cents($reconciliation->expected_amount) !== $this->cents($reconciliation->settled_amount) + $this->cents($reconciliation->fee_amount)) {
            throw new RuntimeException('Settlement amount and fee must equal the expected payment amount.');
        }
    }

    private function quantityAtCost(mixed $quantity, mixed $unitCost): int
    {
        return intdiv(($this->quantityMicros($quantity) * $this->cents($unitCost)) + 500000, 1000000);
    }

    private function nextEntryNumber(string $entryDate): string
    {
        $next = (int) JournalEntry::query()->max('id') + 1;

        return 'JE-'.str_replace('-', '', $entryDate).'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    private function cents(mixed $value): int
    {
        return $this->decimalToScaleInt($value, 2);
    }

    private function quantityMicros(mixed $value): int
    {
        return $this->decimalToScaleInt($value, 6);
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
