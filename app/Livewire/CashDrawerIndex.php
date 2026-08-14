<?php

namespace App\Livewire;

use App\Models\CashDrawerEntry;
use App\Models\CashDrawerShift;
use App\Support\CashDrawerManager;
use Livewire\Component;

class CashDrawerIndex extends Component
{
    public string $search = '';

    public string $statusFilter = 'all';

    public string $openingFloat = '0.00';

    public string $openingNotes = '';

    public string $entryType = CashDrawerEntry::TYPE_CASH_IN;

    public string $entryAmount = '';

    public string $entryReason = '';

    public string $countedClosingCash = '';

    public string $closingNotes = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('cash_drawer.view'), 403);
    }

    public function openShift(): void
    {
        abort_unless(auth()->user()?->hasPermission('cash_drawer.manage'), 403);

        $this->validate([
            'openingFloat' => ['required', 'regex:/^\d{1,12}(?:\.\d{1,2})?$/'],
            'openingNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        app(CashDrawerManager::class)->open($this->openingFloat, $this->openingNotes, auth()->user());
        $this->reset(['openingFloat', 'openingNotes']);
        $this->openingFloat = '0.00';
        session()->flash('status', 'Cash drawer shift opened.');
    }

    public function addEntry(): void
    {
        abort_unless(auth()->user()?->hasPermission('cash_drawer.manage'), 403);

        $this->validate([
            'entryType' => ['required', 'in:cash_in,cash_out'],
            'entryAmount' => ['required', 'regex:/^\d{1,12}(?:\.\d{1,2})?$/'],
            'entryReason' => ['required', 'string', 'max:180'],
        ]);

        $shift = app(CashDrawerManager::class)->currentOpen();
        abort_if(! $shift, 422, 'Open a cash drawer shift before recording a cash movement.');

        app(CashDrawerManager::class)->recordEntry(
            $shift,
            $this->entryType,
            $this->entryAmount,
            $this->entryReason,
            auth()->user()
        );

        $this->reset(['entryAmount', 'entryReason']);
        session()->flash('status', 'Cash drawer movement recorded.');
    }

    public function closeShift(): void
    {
        abort_unless(auth()->user()?->hasPermission('cash_drawer.manage'), 403);

        $this->validate([
            'countedClosingCash' => ['required', 'regex:/^\d{1,12}(?:\.\d{1,2})?$/'],
            'closingNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        $shift = app(CashDrawerManager::class)->currentOpen();
        abort_if(! $shift, 422, 'There is no open cash drawer shift to close.');

        app(CashDrawerManager::class)->close(
            $shift,
            $this->countedClosingCash,
            $this->closingNotes,
            auth()->user()
        );

        $this->reset(['countedClosingCash', 'closingNotes']);
        session()->flash('status', 'Cash drawer shift closed and reconciled.');
    }

    public function render()
    {
        $manager = app(CashDrawerManager::class);
        $currentShift = $manager->currentOpen();

        return view('livewire.cash-drawer-index', [
            'currentShift' => $currentShift,
            'currentTotals' => $currentShift ? $manager->calculateTotals($currentShift) : null,
            'shifts' => $this->shiftsQuery()->with(['openedBy', 'closedBy'])->latest('opened_at')->limit(75)->get(),
            'stats' => [
                'open' => CashDrawerShift::query()->where('status', CashDrawerShift::STATUS_OPEN)->count(),
                'closed_today' => CashDrawerShift::query()->where('status', CashDrawerShift::STATUS_CLOSED)->whereDate('closed_at', today())->count(),
                'cash_sales_today' => CashDrawerShift::query()->whereDate('opened_at', today())->sum('cash_sales_amount'),
                'variance_today' => CashDrawerShift::query()->where('status', CashDrawerShift::STATUS_CLOSED)->whereDate('closed_at', today())->sum('variance_amount'),
            ],
            'canManage' => auth()->user()?->hasPermission('cash_drawer.manage') ?? false,
        ]);
    }

    private function shiftsQuery()
    {
        return CashDrawerShift::query()
            ->when($this->statusFilter === CashDrawerShift::STATUS_OPEN, fn ($query) => $query->where('status', CashDrawerShift::STATUS_OPEN))
            ->when($this->statusFilter === CashDrawerShift::STATUS_CLOSED, fn ($query) => $query->where('status', CashDrawerShift::STATUS_CLOSED))
            ->when($this->search !== '', function ($query): void {
                $search = '%'.$this->search.'%';

                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('shift_number', 'like', $search)
                        ->orWhereHas('openedBy', fn ($query) => $query->where('name', 'like', $search)->orWhere('email', 'like', $search))
                        ->orWhereHas('closedBy', fn ($query) => $query->where('name', 'like', $search)->orWhere('email', 'like', $search));
                });
            });
    }
}
