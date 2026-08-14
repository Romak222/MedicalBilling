<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\Doctor;
use App\Models\Patient;
use App\Support\PatientRegistry;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PatientForm extends Component
{
    public ?int $patientId = null;

    public array $patient = [
        'customer_id' => '',
        'primary_doctor_id' => '',
        'full_name' => '',
        'patient_code' => '',
        'phone' => '',
        'email' => '',
        'date_of_birth' => '',
        'gender' => '',
        'primary_doctor_name' => '',
        'address_line_1' => '',
        'address_line_2' => '',
        'city' => '',
        'state' => '',
        'postal_code' => '',
        'allergies' => '',
        'medical_notes' => '',
        'notes' => '',
        'reminder_consent' => false,
        'whatsapp_consent' => false,
        'sms_consent' => false,
    ];

    public function mount(?Patient $record = null): void
    {
        abort_unless(auth()->user()?->hasPermission('patients.manage'), 403);

        if ($record?->exists) {
            $this->fillFromPatient($record->load('customer'));
        }
    }

    public function save(): mixed
    {
        abort_unless(auth()->user()?->hasPermission('patients.manage'), 403);

        $this->patient['customer_id'] = $this->patient['customer_id'] === '' ? null : $this->patient['customer_id'];
        $this->patient['reminder_consent'] = (bool) ($this->patient['reminder_consent'] ?? false);
        $this->patient['whatsapp_consent'] = (bool) ($this->patient['whatsapp_consent'] ?? false);
        $this->patient['sms_consent'] = (bool) ($this->patient['sms_consent'] ?? false);
        $validated = $this->validate();
        $registry = app(PatientRegistry::class);

        if ($this->patientId) {
            $patient = $registry->updatePatient(Patient::query()->findOrFail($this->patientId), $validated, auth()->user());
            session()->flash('status', 'Patient updated.');
        } else {
            $patient = $registry->createPatient($validated, auth()->user());
            session()->flash('status', 'Patient added.');
        }

        return $this->redirectRoute('patients.show', $patient, navigate: false);
    }

    public function render()
    {
        return view('livewire.patient-form', [
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(),
            'doctors' => Doctor::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'patient.customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'patient.primary_doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
            'patient.full_name' => ['required', 'string', 'max:180'],
            'patient.patient_code' => ['nullable', 'string', 'max:80', 'alpha_dash', Rule::unique('patients', 'patient_code')->ignore($this->patientId)],
            'patient.phone' => ['nullable', 'string', 'max:40'],
            'patient.email' => ['nullable', 'email', 'max:255'],
            'patient.date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'patient.gender' => ['nullable', 'string', 'max:30'],
            'patient.primary_doctor_name' => ['nullable', 'string', 'max:180'],
            'patient.address_line_1' => ['nullable', 'string', 'max:200'],
            'patient.address_line_2' => ['nullable', 'string', 'max:200'],
            'patient.city' => ['nullable', 'string', 'max:120'],
            'patient.state' => ['nullable', 'string', 'max:120'],
            'patient.postal_code' => ['nullable', 'string', 'max:20'],
            'patient.allergies' => ['nullable', 'string', 'max:5000'],
            'patient.medical_notes' => ['nullable', 'string', 'max:5000'],
            'patient.notes' => ['nullable', 'string', 'max:5000'],
            'patient.reminder_consent' => ['boolean'],
            'patient.whatsapp_consent' => ['boolean'],
            'patient.sms_consent' => ['boolean'],
        ];
    }

    private function fillFromPatient(Patient $patient): void
    {
        $this->patientId = $patient->id;
        $this->patient = [
            'customer_id' => $patient->customer_id ? (string) $patient->customer_id : '',
            'primary_doctor_id' => $patient->primary_doctor_id ? (string) $patient->primary_doctor_id : '',
            'full_name' => $patient->full_name,
            'patient_code' => $patient->patient_code ?? '',
            'phone' => $patient->phone ?? '',
            'email' => $patient->email ?? '',
            'date_of_birth' => $patient->date_of_birth?->format('Y-m-d') ?? '',
            'gender' => $patient->gender ?? '',
            'primary_doctor_name' => $patient->primary_doctor_name ?? '',
            'address_line_1' => $patient->address_line_1 ?? '',
            'address_line_2' => $patient->address_line_2 ?? '',
            'city' => $patient->city ?? '',
            'state' => $patient->state ?? '',
            'postal_code' => $patient->postal_code ?? '',
            'allergies' => $patient->allergies ?? '',
            'medical_notes' => $patient->medical_notes ?? '',
            'notes' => $patient->notes ?? '',
            'reminder_consent' => (bool) $patient->reminder_consent,
            'whatsapp_consent' => (bool) $patient->whatsapp_consent,
            'sms_consent' => (bool) $patient->sms_consent,
        ];
    }
}
