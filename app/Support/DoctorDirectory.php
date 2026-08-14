<?php

namespace App\Support;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class DoctorDirectory
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function createDoctor(array $payload, User $actor): Doctor
    {
        return DB::transaction(function () use ($payload, $actor): Doctor {
            $attributes = $this->doctorAttributes($payload, $actor);
            $attributes['created_by'] = $actor->id;
            $attributes['is_active'] = true;

            $doctor = Doctor::query()->create($attributes);

            app(AuditLogger::class)->record('doctor.created', $actor, $doctor, $this->auditMetadata($doctor));

            return $doctor->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateDoctor(Doctor $doctor, array $payload, User $actor): Doctor
    {
        return DB::transaction(function () use ($doctor, $payload, $actor): Doctor {
            $doctor->update($this->doctorAttributes($payload, $actor));

            app(AuditLogger::class)->record('doctor.updated', $actor, $doctor, $this->auditMetadata($doctor));

            return $doctor->refresh();
        });
    }

    public function deactivateDoctor(Doctor $doctor, User $actor): Doctor
    {
        return DB::transaction(function () use ($doctor, $actor): Doctor {
            $doctor->update([
                'is_active' => false,
                'updated_by' => $actor->id,
            ]);

            app(AuditLogger::class)->record('doctor.deactivated', $actor, $doctor, $this->auditMetadata($doctor));

            return $doctor->refresh();
        });
    }

    public function restoreDoctor(Doctor $doctor, User $actor): Doctor
    {
        return DB::transaction(function () use ($doctor, $actor): Doctor {
            $doctor->update([
                'is_active' => true,
                'updated_by' => $actor->id,
            ]);

            app(AuditLogger::class)->record('doctor.restored', $actor, $doctor, $this->auditMetadata($doctor));

            return $doctor->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function doctorAttributes(array $payload, User $actor): array
    {
        return [
            'name' => $this->blankToNull(Arr::get($payload, 'doctor.name')),
            'registration_number' => $this->uppercaseOrNull(Arr::get($payload, 'doctor.registration_number')),
            'specialization' => $this->blankToNull(Arr::get($payload, 'doctor.specialization')),
            'clinic_name' => $this->blankToNull(Arr::get($payload, 'doctor.clinic_name')),
            'phone' => $this->blankToNull(Arr::get($payload, 'doctor.phone')),
            'alternate_phone' => $this->blankToNull(Arr::get($payload, 'doctor.alternate_phone')),
            'email' => $this->blankToNull(Arr::get($payload, 'doctor.email')),
            'address_line_1' => $this->blankToNull(Arr::get($payload, 'doctor.address_line_1')),
            'address_line_2' => $this->blankToNull(Arr::get($payload, 'doctor.address_line_2')),
            'city' => $this->blankToNull(Arr::get($payload, 'doctor.city')),
            'state' => $this->blankToNull(Arr::get($payload, 'doctor.state')),
            'postal_code' => $this->blankToNull(Arr::get($payload, 'doctor.postal_code')),
            'notes' => $this->blankToNull(Arr::get($payload, 'doctor.notes')),
            'updated_by' => $actor->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function auditMetadata(Doctor $doctor): array
    {
        return [
            'registration_number' => $doctor->registration_number,
            'specialization' => $doctor->specialization,
            'clinic_name' => $doctor->clinic_name,
            'city' => $doctor->city,
        ];
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
