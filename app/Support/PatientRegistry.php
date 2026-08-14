<?php

namespace App\Support;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PatientRegistry
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function createPatient(array $payload, User $actor): Patient
    {
        return DB::transaction(function () use ($payload, $actor): Patient {
            $attributes = $this->patientAttributes($payload, $actor);
            $attributes['created_by'] = $actor->id;
            $attributes['is_active'] = true;

            $patient = Patient::query()->create($attributes);

            app(AuditLogger::class)->record('patient.created', $actor, $patient, $this->auditMetadata($patient));

            return $patient->refresh()->load('customer');
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updatePatient(Patient $patient, array $payload, User $actor): Patient
    {
        return DB::transaction(function () use ($patient, $payload, $actor): Patient {
            $patient->update($this->patientAttributes($payload, $actor));

            app(AuditLogger::class)->record('patient.updated', $actor, $patient, $this->auditMetadata($patient));

            return $patient->refresh()->load('customer');
        });
    }

    public function deactivatePatient(Patient $patient, User $actor): Patient
    {
        return DB::transaction(function () use ($patient, $actor): Patient {
            $patient->update([
                'is_active' => false,
                'updated_by' => $actor->id,
            ]);

            app(AuditLogger::class)->record('patient.deactivated', $actor, $patient, $this->auditMetadata($patient));

            return $patient->refresh()->load('customer');
        });
    }

    public function restorePatient(Patient $patient, User $actor): Patient
    {
        return DB::transaction(function () use ($patient, $actor): Patient {
            $patient->update([
                'is_active' => true,
                'updated_by' => $actor->id,
            ]);

            app(AuditLogger::class)->record('patient.restored', $actor, $patient, $this->auditMetadata($patient));

            return $patient->refresh()->load('customer');
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function patientAttributes(array $payload, User $actor): array
    {
        $customerId = $this->blankToNull(Arr::get($payload, 'patient.customer_id'));
        $doctorId = $this->blankToNull(Arr::get($payload, 'patient.primary_doctor_id'));

        return [
            'customer_id' => $customerId,
            'primary_doctor_id' => $doctorId,
            'full_name' => $this->blankToNull(Arr::get($payload, 'patient.full_name')),
            'patient_code' => $this->uppercaseOrNull(Arr::get($payload, 'patient.patient_code')),
            'phone' => $this->blankToNull(Arr::get($payload, 'patient.phone')),
            'email' => $this->blankToNull(Arr::get($payload, 'patient.email')),
            'date_of_birth' => $this->blankToNull(Arr::get($payload, 'patient.date_of_birth')),
            'gender' => $this->blankToNull(Arr::get($payload, 'patient.gender')),
            'primary_doctor_name' => $this->doctorName($doctorId, Arr::get($payload, 'patient.primary_doctor_name')),
            'address_line_1' => $this->blankToNull(Arr::get($payload, 'patient.address_line_1')),
            'address_line_2' => $this->blankToNull(Arr::get($payload, 'patient.address_line_2')),
            'city' => $this->blankToNull(Arr::get($payload, 'patient.city')),
            'state' => $this->blankToNull(Arr::get($payload, 'patient.state')),
            'postal_code' => $this->blankToNull(Arr::get($payload, 'patient.postal_code')),
            'allergies' => $this->blankToNull(Arr::get($payload, 'patient.allergies')),
            'medical_notes' => $this->blankToNull(Arr::get($payload, 'patient.medical_notes')),
            'notes' => $this->blankToNull(Arr::get($payload, 'patient.notes')),
            'reminder_consent' => (bool) Arr::get($payload, 'patient.reminder_consent'),
            'whatsapp_consent' => (bool) Arr::get($payload, 'patient.whatsapp_consent'),
            'sms_consent' => (bool) Arr::get($payload, 'patient.sms_consent'),
            'updated_by' => $actor->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function auditMetadata(Patient $patient): array
    {
        return [
            'patient_code' => $patient->patient_code,
            'customer_id' => $patient->customer_id,
            'primary_doctor_id' => $patient->primary_doctor_id,
            'doctor_present' => $patient->primary_doctor_name !== null,
            'allergies_present' => $patient->allergies !== null,
            'medical_notes_present' => $patient->medical_notes !== null,
        ];
    }

    private function doctorName(mixed $doctorId, mixed $fallback): ?string
    {
        if ($doctorId !== null) {
            return Doctor::query()->find($doctorId)?->name ?? $this->blankToNull($fallback);
        }

        return $this->blankToNull($fallback);
    }

    private function uppercaseOrNull(mixed $value): ?string
    {
        $value = $this->blankToNull($value);

        return $value === null ? null : strtoupper($value);
    }

    private function blankToNull(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        return $value === '' ? null : $value;
    }
}
