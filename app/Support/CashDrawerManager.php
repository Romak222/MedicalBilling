<?php

namespace App\Support;

use App\Models\CashDrawerEntry;
use App\Models\CashDrawerShift;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashDrawerManager
{
    public function currentOpen(): ?CashDrawerShift
    {
        return CashDrawerShift::query()
            ->where('status', CashDrawerShift::STATUS_OPEN)
            ->latest('opened_at')
            ->first();
    }

    public function open(string $openingFloat, ?string $openingNotes, User $actor): CashDrawerShift
    {
        return DB::transaction(function () use ($openingFloat, $openingNotes, $actor): CashDrawerShift {
            if (CashDrawerShift::query()->where('status', CashDrawerShift::STATUS_OPEN)->exists()) {
                throw ValidationException::withMessages([
                    'opening_float' => 'Close the current cash drawer shift before opening another shift.',
                ]);
            }

            $openingCents = $this->amountToCents($openingFloat, 'opening_float');
            $shift = CashDrawerShift::query()->create([
                'shift_number' => $this->nextShiftNumber(),
                'status' => CashDrawerShift::STATUS_OPEN,
                'opened_at' => now(),
                'opening_float' => $this->formatCents($openingCents),
                'opening_notes' => $this->blankToNull($openingNotes),
                'opened_by' => $actor->id,
            ]);

            app(AuditLogger::class)->record(
                'cash_drawer.shift_opened',
                $actor,
                $shift,
                [
                    'shift_number' => $shift->shift_number,
                    'opening_float' => $shift->opening_float,
                ]
            );

            return $shift->refresh();
        });
    }

    public function recordEntry(
        CashDrawerShift|int $shift,
        string $entryType,
        string $amount,
        string $reason,
        User $actor
    ): CashDrawerEntry {
        return DB::transaction(function () use ($shift, $entryType, $amount, $reason, $actor): CashDrawerEntry {
            $shift = $this->resolveShift($shift, true, true);

            if (! in_array($entryType, [CashDrawerEntry::TYPE_CASH_IN, CashDrawerEntry::TYPE_CASH_OUT], true)) {
                throw ValidationException::withMessages([
                    'entry_type' => 'Choose a valid cash movement type.',
                ]);
            }

            $amountCents = $this->amountToCents($amount, 'amount');
            if ($amountCents <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Cash movement amount must be greater than zero.',
                ]);
            }

            $reason = trim($reason);
            if ($reason === '') {
                throw ValidationException::withMessages([
                    'reason' => 'Enter a reason for this cash movement.',
                ]);
            }

            $entry = $shift->entries()->create([
                'entry_type' => $entryType,
                'amount' => $this->formatCents($amountCents),
                'reason' => $reason,
                'created_by' => $actor->id,
            ]);

            app(AuditLogger::class)->record(
                'cash_drawer.entry_created',
                $actor,
                $entry,
                [
                    'shift_number' => $shift->shift_number,
                    'entry_type' => $entryType,
                    'amount' => $entry->amount,
                    'reason' => $reason,
                ]
            );

            return $entry->refresh();
        });
    }

    public function close(
        CashDrawerShift|int $shift,
        string $countedClosingCash,
        ?string $closingNotes,
        User $actor
    ): CashDrawerShift {
        return DB::transaction(function () use ($shift, $countedClosingCash, $closingNotes, $actor): CashDrawerShift {
            $shift = $this->resolveShift($shift, true, true);
            $countedCents = $this->amountToCents($countedClosingCash, 'counted_closing_cash');
            $totals = $this->calculateTotals($shift);

            $shift->update([
                'status' => CashDrawerShift::STATUS_CLOSED,
                'closed_at' => now(),
                'cash_sales_amount' => $totals['cash_sales_amount'],
                'cash_refunds_amount' => $totals['cash_refunds_amount'],
                'cash_in_amount' => $totals['cash_in_amount'],
                'cash_out_amount' => $totals['cash_out_amount'],
                'expected_closing_cash' => $totals['expected_closing_cash'],
                'counted_closing_cash' => $this->formatCents($countedCents),
                'variance_amount' => $this->formatCents($countedCents - $this->amountToCents($totals['expected_closing_cash'], 'expected_closing_cash')),
                'closing_notes' => $this->blankToNull($closingNotes),
                'closed_by' => $actor->id,
            ]);

            app(AuditLogger::class)->record(
                'cash_drawer.shift_closed',
                $actor,
                $shift,
                [
                    'shift_number' => $shift->shift_number,
                    'expected_closing_cash' => $shift->expected_closing_cash,
                    'counted_closing_cash' => $shift->counted_closing_cash,
                    'variance_amount' => $shift->variance_amount,
                ]
            );

            return $shift->refresh();
        });
    }

    /**
     * @return array<string, string>
     */
    public function calculateTotals(CashDrawerShift|int $shift): array
    {
        $shift = $this->resolveShift($shift);
        $cashSalesCents = $this->sumAmounts(
            SalesInvoice::query()
                ->where('cash_drawer_shift_id', $shift->id)
                ->where('status', SalesInvoice::STATUS_FINALIZED)
                ->where('payment_method', 'cash')
                ->pluck('total_amount')
        );
        $cashRefundsCents = $this->sumAmounts(
            SalesReturn::query()
                ->where('cash_drawer_shift_id', $shift->id)
                ->where('status', SalesReturn::STATUS_FINALIZED)
                ->where('refund_method', 'cash')
                ->pluck('refund_amount')
        );
        $cashInCents = $this->sumAmounts(
            $shift->entries()
                ->where('entry_type', CashDrawerEntry::TYPE_CASH_IN)
                ->pluck('amount')
        );
        $cashOutCents = $this->sumAmounts(
            $shift->entries()
                ->where('entry_type', CashDrawerEntry::TYPE_CASH_OUT)
                ->pluck('amount')
        );
        $openingCents = $this->amountToCents($shift->opening_float, 'opening_float');
        $expectedCents = $openingCents + $cashSalesCents + $cashInCents - $cashRefundsCents - $cashOutCents;

        return [
            'opening_float' => $this->formatCents($openingCents),
            'cash_sales_amount' => $this->formatCents($cashSalesCents),
            'cash_refunds_amount' => $this->formatCents($cashRefundsCents),
            'cash_in_amount' => $this->formatCents($cashInCents),
            'cash_out_amount' => $this->formatCents($cashOutCents),
            'expected_closing_cash' => $this->formatCents($expectedCents),
        ];
    }

    private function resolveShift(CashDrawerShift|int $shift, bool $lock = false, bool $requireOpen = false): CashDrawerShift
    {
        $query = CashDrawerShift::query();
        if ($lock) {
            $query->lockForUpdate();
        }

        $resolved = $query->findOrFail($shift instanceof CashDrawerShift ? $shift->id : $shift);

        if ($requireOpen && ! $resolved->isOpen()) {
            throw ValidationException::withMessages([
                'shift' => 'Only an open cash drawer shift can be changed.',
            ]);
        }

        return $resolved;
    }

    private function nextShiftNumber(): string
    {
        $prefix = 'CD-'.now()->format('Ymd');
        $sequence = CashDrawerShift::query()->where('shift_number', 'like', $prefix.'-%')->count() + 1;

        do {
            $number = $prefix.'-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
            $sequence++;
        } while (CashDrawerShift::query()->where('shift_number', $number)->exists());

        return $number;
    }

    private function sumAmounts(iterable $amounts): int
    {
        $total = 0;
        foreach ($amounts as $amount) {
            $total += $this->amountToCents($amount, 'amount');
        }

        return $total;
    }

    private function amountToCents(mixed $amount, string $field): int
    {
        $value = trim((string) $amount);
        if ($value === '' || ! preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
            throw ValidationException::withMessages([
                $field => 'Enter a valid non-negative amount with up to two decimal places.',
            ]);
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function formatCents(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $absolute = abs($cents);

        return $sign.intdiv($absolute, 100).'.'.str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);
    }

    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
