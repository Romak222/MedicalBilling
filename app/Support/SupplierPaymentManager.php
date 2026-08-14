<?php

namespace App\Support;

use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierPaymentManager
{
    public function supportedMethods(): array
    {
        return ['cash', 'bank_transfer', 'upi', 'cheque', 'other'];
    }

    /**
     * @param  array<string, string|null>  $payload
     */
    public function create(Supplier $supplier, array $payload, User $actor): SupplierPayment
    {
        return DB::transaction(function () use ($supplier, $payload, $actor): SupplierPayment {
            $method = strtolower(trim((string) ($payload['payment_method'] ?? '')));
            $amount = trim((string) ($payload['amount'] ?? ''));

            if (! in_array($method, $this->supportedMethods(), true)) {
                throw ValidationException::withMessages(['payment_method' => 'Choose a valid supplier payment method.']);
            }

            if (! preg_match('/^\d{1,12}(?:\.\d{1,2})?$/', $amount) || $this->cents($amount) <= 0) {
                throw ValidationException::withMessages(['amount' => 'Enter a supplier payment amount greater than zero.']);
            }

            $payment = SupplierPayment::query()->create([
                'supplier_id' => $supplier->id,
                'payment_number' => $this->nextNumber(),
                'payment_date' => $payload['payment_date'] ?? today()->toDateString(),
                'payment_method' => $method,
                'status' => SupplierPayment::STATUS_POSTED,
                'amount' => $amount,
                'reference' => $this->blankToNull($payload['reference'] ?? null),
                'notes' => $this->blankToNull($payload['notes'] ?? null),
                'created_by' => $actor->id,
            ]);

            $journalEntry = app(AccountingManager::class)->postSupplierPayment($payment, $actor);

            app(AuditLogger::class)->record('supplier_payment.created', $actor, $payment, [
                'payment_number' => $payment->payment_number,
                'supplier_id' => $payment->supplier_id,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'journal_entry_id' => $journalEntry->id,
            ]);

            return $payment->refresh()->load(['supplier', 'journalEntry']);
        });
    }

    public function nextNumber(): string
    {
        $next = (int) SupplierPayment::query()->max('id') + 1;

        return 'SP-'.today()->format('Ymd').'-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    private function cents(mixed $value): int
    {
        $value = (string) ($value ?? '0');
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function blankToNull(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        return $value === '' ? null : $value;
    }
}
