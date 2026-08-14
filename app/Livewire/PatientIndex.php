<?php

namespace App\Livewire;

use App\Models\Patient;
use App\Support\PatientRegistry;
use Livewire\Component;

class PatientIndex extends Component
{
    public string $search = '';

    public string $statusFilter = 'active';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('patients.view'), 403);
    }

    public function deactivatePatient(int $patientId): void
    {
        abort_unless(auth()->user()?->hasPermission('patients.manage'), 403);

        app(PatientRegistry::class)->deactivatePatient(Patient::query()->findOrFail($patientId), auth()->user());
        session()->flash('status', 'Patient deleted from active list.');
    }

    public function restorePatient(int $patientId): void
    {
        abort_unless(auth()->user()?->hasPermission('patients.manage'), 403);

        app(PatientRegistry::class)->restorePatient(Patient::query()->findOrFail($patientId), auth()->user());
        session()->flash('status', 'Patient restored.');
    }

    public function render()
    {
        return view('livewire.patient-index', [
            'patients' => $this->patientsQuery()
                ->with(['customer', 'doctor'])
                ->withCount('salesInvoices')
                ->latest()
                ->limit(75)
                ->get(),
            'stats' => [
                'total' => Patient::query()->count(),
                'active' => Patient::query()->where('is_active', true)->count(),
                'inactive' => Patient::query()->where('is_active', false)->count(),
                'linked_customers' => Patient::query()->whereNotNull('customer_id')->count(),
                'with_doctor' => Patient::query()->where(function ($query): void {
                    $query->whereNotNull('primary_doctor_id')->orWhereNotNull('primary_doctor_name');
                })->count(),
            ],
            'canManage' => auth()->user()?->hasPermission('patients.manage') ?? false,
        ]);
    }

    private function patientsQuery()
    {
        return Patient::query()
            ->when($this->statusFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($this->search !== '', function ($query): void {
                $search = '%'.$this->search.'%';

                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('full_name', 'like', $search)
                        ->orWhere('patient_code', 'like', $search)
                        ->orWhere('phone', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhere('primary_doctor_name', 'like', $search)
                        ->orWhere('city', 'like', $search)
                        ->orWhereHas('customer', function ($query) use ($search): void {
                            $query
                                ->where('name', 'like', $search)
                                ->orWhere('code', 'like', $search)
                                ->orWhere('phone', 'like', $search);
                        });
                });
            });
    }
}
