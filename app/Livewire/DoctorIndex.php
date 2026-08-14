<?php

namespace App\Livewire;

use App\Models\Doctor;
use App\Support\DoctorDirectory;
use Livewire\Component;

class DoctorIndex extends Component
{
    public string $search = '';

    public string $statusFilter = 'active';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('doctors.view'), 403);
    }

    public function deactivateDoctor(int $doctorId): void
    {
        abort_unless(auth()->user()?->hasPermission('doctors.manage'), 403);

        app(DoctorDirectory::class)->deactivateDoctor(Doctor::query()->findOrFail($doctorId), auth()->user());
        session()->flash('status', 'Doctor deleted from active list.');
    }

    public function restoreDoctor(int $doctorId): void
    {
        abort_unless(auth()->user()?->hasPermission('doctors.manage'), 403);

        app(DoctorDirectory::class)->restoreDoctor(Doctor::query()->findOrFail($doctorId), auth()->user());
        session()->flash('status', 'Doctor restored.');
    }

    public function render()
    {
        return view('livewire.doctor-index', [
            'doctors' => $this->doctorsQuery()
                ->withCount(['patients', 'prescriptions'])
                ->latest()
                ->limit(75)
                ->get(),
            'stats' => [
                'total' => Doctor::query()->count(),
                'active' => Doctor::query()->where('is_active', true)->count(),
                'inactive' => Doctor::query()->where('is_active', false)->count(),
                'with_patients' => Doctor::query()->has('patients')->count(),
                'with_prescriptions' => Doctor::query()->has('prescriptions')->count(),
            ],
            'canManage' => auth()->user()?->hasPermission('doctors.manage') ?? false,
        ]);
    }

    private function doctorsQuery()
    {
        return Doctor::query()
            ->when($this->statusFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($this->search !== '', function ($query): void {
                $search = '%'.$this->search.'%';

                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', $search)
                        ->orWhere('registration_number', 'like', $search)
                        ->orWhere('specialization', 'like', $search)
                        ->orWhere('clinic_name', 'like', $search)
                        ->orWhere('phone', 'like', $search)
                        ->orWhere('city', 'like', $search)
                        ->orWhere('state', 'like', $search);
                });
            });
    }
}
