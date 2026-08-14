<?php

namespace App\Livewire;

use App\Models\ControlledMedicineRegisterEntry;
use Livewire\Component;

class ControlledMedicineRegisterIndex extends Component
{
    public string $search = '';

    public string $entryTypeFilter = 'all';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('controlled_medicines.view'), 403);
    }

    public function render()
    {
        return view('livewire.controlled-medicine-register-index', [
            'entries' => $this->entriesQuery()
                ->with(['product', 'patient', 'doctor', 'prescription', 'salesInvoice', 'salesReturn'])
                ->orderByDesc('event_date')
                ->orderByDesc('id')
                ->limit(100)
                ->get(),
            'stats' => [
                'total' => ControlledMedicineRegisterEntry::query()->count(),
                'dispensed' => ControlledMedicineRegisterEntry::query()->where('entry_type', ControlledMedicineRegisterEntry::TYPE_SALE)->count(),
                'cancelled' => ControlledMedicineRegisterEntry::query()->where('entry_type', ControlledMedicineRegisterEntry::TYPE_SALE_CANCEL)->count(),
                'returned' => ControlledMedicineRegisterEntry::query()->where('entry_type', ControlledMedicineRegisterEntry::TYPE_SALE_RETURN)->count(),
                'controlled_products' => ControlledMedicineRegisterEntry::query()->distinct('product_id')->whereNotNull('product_id')->count('product_id'),
            ],
        ]);
    }

    private function entriesQuery()
    {
        return ControlledMedicineRegisterEntry::query()
            ->when(
                in_array($this->entryTypeFilter, [
                    ControlledMedicineRegisterEntry::TYPE_SALE,
                    ControlledMedicineRegisterEntry::TYPE_SALE_CANCEL,
                    ControlledMedicineRegisterEntry::TYPE_SALE_RETURN,
                ], true),
                fn ($query) => $query->where('entry_type', $this->entryTypeFilter)
            )
            ->when($this->search !== '', function ($query): void {
                $search = '%'.$this->search.'%';

                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('product_name_snapshot', 'like', $search)
                        ->orWhere('batch_number_snapshot', 'like', $search)
                        ->orWhere('patient_name_snapshot', 'like', $search)
                        ->orWhere('doctor_name_snapshot', 'like', $search)
                        ->orWhere('prescription_number_snapshot', 'like', $search)
                        ->orWhere('invoice_number_snapshot', 'like', $search)
                        ->orWhere('return_number_snapshot', 'like', $search)
                        ->orWhereHas('product', function ($query) use ($search): void {
                            $query
                                ->where('name', 'like', $search)
                                ->orWhere('sku', 'like', $search);
                        });
                });
            });
    }
}
