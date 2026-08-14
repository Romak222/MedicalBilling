<?php

namespace App\Support;

use App\Models\PaymentReconciliation;
use App\Models\SalesInvoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentReconciliationManager
{
    /**
     * @return array<int, string>
     */
    public function supportedMethods(): array
    {
        return ['card', 'upi', 'mixed'];
    }

    public function expectedAmount(string $paymentMethod, string $fromDate, string $toDate): string
    {
        $cents = $this->cents(SalesInvoice::query()
            ->where('status', SalesInvoice::STATUS_FINALIZED)
            ->where('payment_method', strtolower($paymentMethod))
            ->whereDate('invoice_date', '>=', $fromDate)
            ->whereDate('invoice_date', '<=', $toDate)
            ->sum('total_amount'));

        return $this->formatCents($cents);
    }

    /**
     * @param  array<string, string|null>  $payload
     */
    public function create(array $payload, User $actor): PaymentReconciliation
    {
        return DB::transaction(function () use ($payload, $actor): PaymentReconciliation {
            $method = strtolower(trim((string) ($payload['payment_method'] ?? '')));
            $fromDate = (string) ($payload['period_from'] ?? '');
            $toDate = (string) ($payload['period_to'] ?? '');
            $settlementReference = strtoupper(trim((string) ($payload['settlement_reference'] ?? '')));
            $expectedAmount = $this->expectedAmount($method, $fromDate, $toDate);
            $settledAmount = $this->decimalOrZero($payload['settled_amount'] ?? null);
            $feeAmount = $this->decimalOrZero($payload['fee_amount'] ?? null);

            if (! in_array($method, $this->supportedMethods(), true)) {
                throw ValidationException::withMessages(['payment_method' => 'Choose card, UPI, or mixed payment reconciliation.']);
            }

            if ($fromDate > $toDate) {
                throw ValidationException::withMessages(['period_to' => 'The settlement period end must be on or after the start date.']);
            }

            if ($settlementReference === '') {
                throw ValidationException::withMessages(['settlement_reference' => 'Enter the provider settlement reference.']);
            }

            if ($this->cents($expectedAmount) <= 0) {
                throw ValidationException::withMessages(['period_from' => 'There are no finalized payments of this method in the selected period.']);
            }

            if ($this->cents($settledAmount) < 0 || $this->cents($feeAmount) < 0) {
                throw ValidationException::withMessages(['settled_amount' => 'Settlement and fee amounts cannot be negative.']);
            }

            if ($this->cents($expectedAmount) !== $this->cents($settledAmount) + $this->cents($feeAmount)) {
                throw ValidationException::withMessages(['settled_amount' => 'Settlement amount plus provider fee must equal the expected amount.']);
            }

            $overlapping = PaymentReconciliation::query()
                ->where('payment_method', $method)
                ->where('status', 'reconciled')
                ->whereDate('period_from', '<=', $toDate)
                ->whereDate('period_to', '>=', $fromDate)
                ->exists();

            if ($overlapping) {
                throw ValidationException::withMessages(['period_from' => 'This payment period overlaps an existing reconciliation.']);
            }

            $reconciliation = PaymentReconciliation::query()->create([
                'reconciliation_number' => $this->nextNumber(),
                'payment_method' => $method,
                'period_from' => $fromDate,
                'period_to' => $toDate,
                'settlement_date' => $payload['settlement_date'] ?? today()->toDateString(),
                'settlement_reference' => $settlementReference,
                'expected_amount' => $expectedAmount,
                'settled_amount' => $settledAmount,
                'fee_amount' => $feeAmount,
                'status' => 'pending',
                'notes' => $this->blankToNull($payload['notes'] ?? null),
                'created_by' => $actor->id,
            ]);

            $journalEntry = app(AccountingManager::class)->postPaymentReconciliation($reconciliation, $actor);

            app(AuditLogger::class)->record('payment_reconciliation.created', $actor, $reconciliation, [
                'reconciliation_number' => $reconciliation->reconciliation_number,
                'payment_method' => $reconciliation->payment_method,
                'period_from' => $reconciliation->period_from?->toDateString(),
                'period_to' => $reconciliation->period_to?->toDateString(),
                'expected_amount' => $reconciliation->expected_amount,
                'settled_amount' => $reconciliation->settled_amount,
                'fee_amount' => $reconciliation->fee_amount,
                'journal_entry_id' => $journalEntry->id,
            ]);

            return $reconciliation->refresh()->load('journalEntry');
        });
    }

    public function nextNumber(): string
    {
        $next = (int) PaymentReconciliation::query()->max('id') + 1;

        return 'PR-'.today()->format('Ymd').'-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    private function decimalOrZero(mixed $value): string
    {
        return (string) ($this->blankToNull($value) ?? '0');
    }

    private function blankToNull(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        return $value === '' ? null : $value;
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
