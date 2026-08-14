<?php

namespace App\Livewire;

use App\Support\GstReportService;
use Carbon\CarbonImmutable;
use Livewire\Component;

class GstReportIndex extends Component
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
        }
    }

    public function render()
    {
        [$fromDate, $toDate] = $this->safeRange();

        return view('livewire.gst-report-index', [
            'report' => app(GstReportService::class)->summary($fromDate, $toDate),
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);
    }

    private function safeRange(): array
    {
        $from = $this->validDate($this->fromDate) ? $this->fromDate : today()->startOfMonth()->toDateString();
        $to = $this->validDate($this->toDate) ? $this->toDate : today()->toDateString();

        return $from <= $to ? [$from, $to] : [$to, $from];
    }

    private function validDate(string $value): bool
    {
        try {
            return CarbonImmutable::createFromFormat('!Y-m-d', $value)->format('Y-m-d') === $value;
        } catch (\Throwable) {
            return false;
        }
    }
}
