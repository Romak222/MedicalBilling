<?php

namespace App\Livewire;

use App\Models\PrescriptionItem;
use Livewire\Component;

class PrescriptionRefillIndex extends Component
{
    public string $search = '';

    public string $statusFilter = 'all';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('prescriptions.view'), 403);
    }

    public function render()
    {
        $trackedItems = $this->itemsQuery()
            ->with(['prescription.patient.customer', 'prescription.doctor', 'product'])
            ->get();

        return view('livewire.prescription-refill-index', [
            'items' => $trackedItems
                ->filter(fn (PrescriptionItem $item): bool => $this->matchesStatusFilter($item))
                ->sortBy(fn (PrescriptionItem $item): string => $this->sortKey($item))
                ->take(100)
                ->values(),
            'stats' => [
                'tracked' => $trackedItems->count(),
                'overdue' => $trackedItems->filter(fn (PrescriptionItem $item): bool => $item->refillStatus() === PrescriptionItem::REFILL_STATUS_OVERDUE)->count(),
                'due' => $trackedItems->filter(fn (PrescriptionItem $item): bool => $item->refillStatus() === PrescriptionItem::REFILL_STATUS_DUE)->count(),
                'pending' => $trackedItems->filter(fn (PrescriptionItem $item): bool => $item->refillStatus() === PrescriptionItem::REFILL_STATUS_PENDING)->count(),
                'upcoming' => $trackedItems->filter(fn (PrescriptionItem $item): bool => $item->refillStatus() === PrescriptionItem::REFILL_STATUS_UPCOMING)->count(),
                'completed' => $trackedItems->filter(fn (PrescriptionItem $item): bool => $item->refillStatus() === PrescriptionItem::REFILL_STATUS_COMPLETED)->count(),
            ],
        ]);
    }

    private function itemsQuery()
    {
        return PrescriptionItem::query()
            ->whereNotNull('refill_interval_days')
            ->where('refill_interval_days', '>', 0)
            ->when($this->search !== '', function ($query): void {
                $search = '%'.$this->search.'%';

                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('medicine_name_snapshot', 'like', $search)
                        ->orWhereHas('product', function ($query) use ($search): void {
                            $query
                                ->where('name', 'like', $search)
                                ->orWhere('sku', 'like', $search);
                        })
                        ->orWhereHas('prescription', function ($query) use ($search): void {
                            $query
                                ->where('prescription_number', 'like', $search)
                                ->orWhere('patient_name_snapshot', 'like', $search)
                                ->orWhere('doctor_name_snapshot', 'like', $search)
                                ->orWhereHas('patient', function ($query) use ($search): void {
                                    $query
                                        ->where('full_name', 'like', $search)
                                        ->orWhere('patient_code', 'like', $search)
                                        ->orWhere('phone', 'like', $search);
                                });
                        });
                });
            });
    }

    private function matchesStatusFilter(PrescriptionItem $item): bool
    {
        if ($this->statusFilter === 'all') {
            return true;
        }

        return $item->refillStatus() === $this->statusFilter;
    }

    private function sortKey(PrescriptionItem $item): string
    {
        $weight = match ($item->refillStatus()) {
            PrescriptionItem::REFILL_STATUS_OVERDUE => 1,
            PrescriptionItem::REFILL_STATUS_DUE => 2,
            PrescriptionItem::REFILL_STATUS_PENDING => 3,
            PrescriptionItem::REFILL_STATUS_UPCOMING => 4,
            PrescriptionItem::REFILL_STATUS_COMPLETED => 5,
            PrescriptionItem::REFILL_STATUS_EXPIRED => 6,
            PrescriptionItem::REFILL_STATUS_ARCHIVED => 7,
            default => 8,
        };

        return sprintf(
            '%02d|%s|%010d',
            $weight,
            $item->next_refill_due_on?->toDateString() ?? '9999-12-31',
            $item->id
        );
    }
}
