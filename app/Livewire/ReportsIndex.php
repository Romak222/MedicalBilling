<?php

namespace App\Livewire;

use App\Support\OperationalReportService;
use Carbon\CarbonImmutable;
use Livewire\Component;

class ReportsIndex extends Component
{
    public string $fromDate = '';

    public string $toDate = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('reports.view'), 403);

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

        session()->flash('status', 'Report period updated.');
    }

    public function resetFilters(): void
    {
        $this->fromDate = today()->startOfMonth()->toDateString();
        $this->toDate = today()->toDateString();
    }

    public function render()
    {
        [$fromDate, $toDate] = $this->safeRange();

        return view('livewire.reports-index', [
            'report' => app(OperationalReportService::class)->dashboard($fromDate, $toDate),
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
}
