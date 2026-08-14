<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Carbon\CarbonImmutable;
use Livewire\Component;

class AccountingIndex extends Component
{
    public string $fromDate = '';

    public string $toDate = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('accounting.view'), 403);

        $this->fromDate = today()->startOfMonth()->toDateString();
        $this->toDate = today()->toDateString();
    }

    public function applyFilters(): void
    {
        $validated = $this->validate([
            'fromDate' => ['required', 'date_format:Y-m-d'],
            'toDate' => ['required', 'date_format:Y-m-d'],
        ]);

        if ($validated['fromDate'] > $validated['toDate']) {
            $this->addError('toDate', 'The end date must be on or after the start date.');

            return;
        }

        session()->flash('status', 'Accounting period updated.');
    }

    public function resetFilters(): void
    {
        $this->fromDate = today()->startOfMonth()->toDateString();
        $this->toDate = today()->toDateString();
    }

    public function render()
    {
        [$fromDate, $toDate] = $this->safeRange();

        $period = fn ($query) => $query
            ->whereDate('entry_date', '>=', $fromDate)
            ->whereDate('entry_date', '<=', $toDate)
            ->where('status', JournalEntry::STATUS_POSTED);

        $entries = JournalEntry::query()
            ->with(['lines.account'])
            ->whereDate('entry_date', '>=', $fromDate)
            ->whereDate('entry_date', '<=', $toDate)
            ->where('status', JournalEntry::STATUS_POSTED)
            ->latest('entry_date')
            ->latest('id')
            ->limit(40)
            ->get();

        $totals = JournalEntryLine::query()
            ->selectRaw('SUM(debit) as debit_total, SUM(credit) as credit_total')
            ->whereHas('journalEntry', $period)
            ->first();

        $accountTotals = JournalEntryLine::query()
            ->selectRaw('account_id, SUM(debit) as debit_total, SUM(credit) as credit_total')
            ->whereHas('journalEntry', $period)
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        $accounts = Account::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(function (Account $account) use ($accountTotals): array {
                $totals = $accountTotals->get($account->id);
                $debitCents = $this->cents($totals?->debit_total);
                $creditCents = $this->cents($totals?->credit_total);
                $balanceCents = $account->normal_balance === 'credit'
                    ? $creditCents - $debitCents
                    : $debitCents - $creditCents;

                return [
                    'account' => $account,
                    'debit' => $this->formatCents($debitCents),
                    'credit' => $this->formatCents($creditCents),
                    'balance' => $this->formatCents($balanceCents),
                ];
            });

        $journalRows = $entries->map(fn (JournalEntry $entry): array => [
            'entry' => $entry,
            'debit' => $this->formatCents($entry->lines->sum(fn ($line): int => $this->cents($line->debit))),
        ]);

        return view('livewire.accounting-index', [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'entries' => $journalRows,
            'accounts' => $accounts,
            'entryCount' => $entries->count(),
            'debitTotal' => $this->formatCents($this->cents($totals?->debit_total)),
            'creditTotal' => $this->formatCents($this->cents($totals?->credit_total)),
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function safeRange(): array
    {
        $defaultFrom = today()->startOfMonth()->toDateString();
        $defaultTo = today()->toDateString();
        $from = $this->isValidDate($this->fromDate) ? $this->fromDate : $defaultFrom;
        $to = $this->isValidDate($this->toDate) ? $this->toDate : $defaultTo;

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    private function isValidDate(string $value): bool
    {
        try {
            return CarbonImmutable::createFromFormat('!Y-m-d', $value)->format('Y-m-d') === $value;
        } catch (\Throwable) {
            return false;
        }
    }

    private function cents(mixed $value): int
    {
        $value = (string) ($value ?? '0');
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) ($whole ?: 0) * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function formatCents(int $value): string
    {
        $sign = $value < 0 ? '-' : '';
        $value = abs($value);

        return sprintf('%s%d.%02d', $sign, intdiv($value, 100), $value % 100);
    }
}
