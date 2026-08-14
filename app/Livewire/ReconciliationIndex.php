<?php

namespace App\Livewire;

use App\Models\PaymentReconciliation;
use App\Support\PaymentReconciliationManager;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ReconciliationIndex extends Component
{
    public string $paymentMethod = 'card';

    public string $periodFrom = '';

    public string $periodTo = '';

    public string $settlementDate = '';

    public string $settlementReference = '';

    public string $settledAmount = '';

    public string $feeAmount = '0.00';

    public string $notes = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('accounting.view'), 403);

        $this->periodFrom = today()->startOfMonth()->toDateString();
        $this->periodTo = today()->toDateString();
        $this->settlementDate = today()->toDateString();
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->hasPermission('accounting.manage'), 403);

        $this->resetValidation();
        $validated = $this->validate([
            'paymentMethod' => [Rule::in(app(PaymentReconciliationManager::class)->supportedMethods())],
            'periodFrom' => ['required', 'date_format:Y-m-d'],
            'periodTo' => ['required', 'date_format:Y-m-d'],
            'settlementDate' => ['required', 'date_format:Y-m-d'],
            'settlementReference' => ['required', 'string', 'max:160', Rule::unique('payment_reconciliations', 'settlement_reference')],
            'settledAmount' => ['required', 'regex:/^\d{1,12}(?:\.\d{1,2})?$/'],
            'feeAmount' => ['required', 'regex:/^\d{1,12}(?:\.\d{1,2})?$/'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            app(PaymentReconciliationManager::class)->create([
                'payment_method' => $validated['paymentMethod'],
                'period_from' => $validated['periodFrom'],
                'period_to' => $validated['periodTo'],
                'settlement_date' => $validated['settlementDate'],
                'settlement_reference' => $validated['settlementReference'],
                'settled_amount' => $validated['settledAmount'],
                'fee_amount' => $validated['feeAmount'],
                'notes' => $validated['notes'],
            ], auth()->user());
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($this->propertyForError($field), $message);
                }
            }

            return;
        }

        $this->reset(['settlementReference', 'settledAmount', 'feeAmount', 'notes']);
        $this->feeAmount = '0.00';
        session()->flash('status', 'Payment settlement reconciled and posted.');
    }

    public function render()
    {
        $manager = app(PaymentReconciliationManager::class);
        [$fromDate, $toDate] = $this->safeRange();
        $method = in_array($this->paymentMethod, $manager->supportedMethods(), true) ? $this->paymentMethod : 'card';

        return view('livewire.reconciliation-index', [
            'expectedAmount' => $manager->expectedAmount($method, $fromDate, $toDate),
            'reconciliations' => PaymentReconciliation::query()
                ->with('journalEntry')
                ->latest('settlement_date')
                ->latest('id')
                ->limit(75)
                ->get(),
            'canManage' => auth()->user()?->hasPermission('accounting.manage') ?? false,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function safeRange(): array
    {
        $defaultFrom = today()->startOfMonth()->toDateString();
        $defaultTo = today()->toDateString();
        $from = $this->isValidDate($this->periodFrom) ? $this->periodFrom : $defaultFrom;
        $to = $this->isValidDate($this->periodTo) ? $this->periodTo : $defaultTo;

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

    private function propertyForError(string $field): string
    {
        return match ($field) {
            'payment_method' => 'paymentMethod',
            'period_from' => 'periodFrom',
            'period_to' => 'periodTo',
            'settlement_date' => 'settlementDate',
            'settlement_reference' => 'settlementReference',
            'settled_amount' => 'settledAmount',
            'fee_amount' => 'feeAmount',
            default => $field,
        };
    }
}
