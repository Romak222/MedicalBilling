<?php

namespace App\Livewire;

use App\Models\Prescription;
use App\Support\PrescriptionRegistry;
use Livewire\Component;

class PrescriptionIndex extends Component
{
    public string $search = '';

    public string $statusFilter = 'open';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('prescriptions.view'), 403);
    }

    public function archivePrescription(int $prescriptionId): void
    {
        abort_unless(auth()->user()?->hasPermission('prescriptions.manage'), 403);

        app(PrescriptionRegistry::class)->archivePrescription(Prescription::query()->findOrFail($prescriptionId), auth()->user());
        session()->flash('status', 'Prescription archived.');
    }

    public function restorePrescription(int $prescriptionId): void
    {
        abort_unless(auth()->user()?->hasPermission('prescriptions.manage'), 403);

        app(PrescriptionRegistry::class)->restorePrescription(Prescription::query()->findOrFail($prescriptionId), auth()->user());
        session()->flash('status', 'Prescription restored.');
    }

    public function render()
    {
        return view('livewire.prescription-index', [
            'prescriptions' => $this->prescriptionsQuery()
                ->with(['patient.customer', 'doctor'])
                ->withCount(['items', 'salesInvoices'])
                ->latest('prescription_date')
                ->limit(75)
                ->get(),
            'stats' => [
                'total' => Prescription::query()->count(),
                'active' => Prescription::query()->where('is_active', true)->count(),
                'archived' => Prescription::query()->where('is_active', false)->count(),
                'open' => Prescription::query()->where('status', Prescription::STATUS_OPEN)->count(),
                'dispensed' => Prescription::query()->where('status', Prescription::STATUS_DISPENSED)->count(),
            ],
            'canManage' => auth()->user()?->hasPermission('prescriptions.manage') ?? false,
        ]);
    }

    private function prescriptionsQuery()
    {
        return Prescription::query()
            ->when($this->statusFilter === 'archived', fn ($query) => $query->where('is_active', false))
            ->when($this->statusFilter !== 'archived', fn ($query) => $query->where('is_active', true))
            ->when(in_array($this->statusFilter, [Prescription::STATUS_OPEN, Prescription::STATUS_PARTIAL, Prescription::STATUS_DISPENSED], true), function ($query): void {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->search !== '', function ($query): void {
                $search = '%'.$this->search.'%';

                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('prescription_number', 'like', $search)
                        ->orWhere('patient_name_snapshot', 'like', $search)
                        ->orWhere('doctor_name_snapshot', 'like', $search)
                        ->orWhere('patient_phone_snapshot', 'like', $search)
                        ->orWhereHas('patient', function ($query) use ($search): void {
                            $query
                                ->where('full_name', 'like', $search)
                                ->orWhere('patient_code', 'like', $search)
                                ->orWhere('phone', 'like', $search);
                        })
                        ->orWhereHas('doctor', function ($query) use ($search): void {
                            $query
                                ->where('name', 'like', $search)
                                ->orWhere('registration_number', 'like', $search)
                                ->orWhere('clinic_name', 'like', $search);
                        });
                });
            });
    }
}
