<?php

namespace App\Livewire;

use App\Models\Doctor;
use App\Support\DoctorDirectory;
use Illuminate\Validation\Rule;
use Livewire\Component;

class DoctorForm extends Component
{
    public ?int $doctorId = null;

    public array $doctor = [
        'name' => '',
        'registration_number' => '',
        'specialization' => '',
        'clinic_name' => '',
        'phone' => '',
        'alternate_phone' => '',
        'email' => '',
        'address_line_1' => '',
        'address_line_2' => '',
        'city' => '',
        'state' => '',
        'postal_code' => '',
        'notes' => '',
    ];

    public function mount(?Doctor $record = null): void
    {
        abort_unless(auth()->user()?->hasPermission('doctors.manage'), 403);

        if ($record?->exists) {
            $this->fillFromDoctor($record);
        }
    }

    public function save(): mixed
    {
        abort_unless(auth()->user()?->hasPermission('doctors.manage'), 403);

        $validated = $this->validate();
        $directory = app(DoctorDirectory::class);

        if ($this->doctorId) {
            $doctor = $directory->updateDoctor(Doctor::query()->findOrFail($this->doctorId), $validated, auth()->user());
            session()->flash('status', 'Doctor updated.');
        } else {
            $doctor = $directory->createDoctor($validated, auth()->user());
            session()->flash('status', 'Doctor added.');
        }

        return $this->redirectRoute('doctors.show', $doctor, navigate: false);
    }

    public function render()
    {
        return view('livewire.doctor-form');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'doctor.name' => ['required', 'string', 'max:180'],
            'doctor.registration_number' => ['nullable', 'string', 'max:120', Rule::unique('doctors', 'registration_number')->ignore($this->doctorId)],
            'doctor.specialization' => ['nullable', 'string', 'max:180'],
            'doctor.clinic_name' => ['nullable', 'string', 'max:180'],
            'doctor.phone' => ['nullable', 'string', 'max:40'],
            'doctor.alternate_phone' => ['nullable', 'string', 'max:40'],
            'doctor.email' => ['nullable', 'email', 'max:255'],
            'doctor.address_line_1' => ['nullable', 'string', 'max:200'],
            'doctor.address_line_2' => ['nullable', 'string', 'max:200'],
            'doctor.city' => ['nullable', 'string', 'max:120'],
            'doctor.state' => ['nullable', 'string', 'max:120'],
            'doctor.postal_code' => ['nullable', 'string', 'max:20'],
            'doctor.notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    private function fillFromDoctor(Doctor $doctor): void
    {
        $this->doctorId = $doctor->id;
        $this->doctor = [
            'name' => $doctor->name,
            'registration_number' => $doctor->registration_number ?? '',
            'specialization' => $doctor->specialization ?? '',
            'clinic_name' => $doctor->clinic_name ?? '',
            'phone' => $doctor->phone ?? '',
            'alternate_phone' => $doctor->alternate_phone ?? '',
            'email' => $doctor->email ?? '',
            'address_line_1' => $doctor->address_line_1 ?? '',
            'address_line_2' => $doctor->address_line_2 ?? '',
            'city' => $doctor->city ?? '',
            'state' => $doctor->state ?? '',
            'postal_code' => $doctor->postal_code ?? '',
            'notes' => $doctor->notes ?? '',
        ];
    }
}
