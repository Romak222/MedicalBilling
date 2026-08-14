<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Database\Seeder;

class DoctorPrescriptionDemoSeeder extends Seeder
{
    public function run(): void
    {
        $actorId = User::query()->orderBy('id')->value('id');
        $patient = Patient::query()->where('patient_code', 'PAT-DEMO-001')->first();

        if (! $patient) {
            return;
        }

        $doctor = Doctor::query()->firstOrCreate(
            ['registration_number' => 'DOC-DEMO-001'],
            [
                'name' => 'Dr Demo',
                'specialization' => 'General Medicine',
                'clinic_name' => 'Demo Clinic',
                'phone' => '9876500003',
                'email' => 'dr-demo@example.test',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'notes' => 'Local demo doctor entry for workflow checks.',
                'is_active' => true,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]
        );

        if ($patient->primary_doctor_id !== $doctor->id) {
            $patient->update([
                'primary_doctor_id' => $doctor->id,
                'primary_doctor_name' => $doctor->name,
                'updated_by' => $actorId,
            ]);
        }

        $prescription = Prescription::query()->firstOrCreate(
            ['prescription_number' => 'RX-DEMO-001'],
            [
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'patient_name_snapshot' => $patient->full_name,
                'patient_phone_snapshot' => $patient->phone,
                'doctor_name_snapshot' => $doctor->name,
                'prescription_date' => '2026-08-12',
                'valid_until' => '2026-09-12',
                'status' => Prescription::STATUS_OPEN,
                'notes' => 'Local demo prescription entry.',
                'pharmacist_notes' => 'Use for workflow verification only.',
                'is_active' => true,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]
        );

        if ($prescription->items()->count() === 0) {
            $prescription->items()->create([
                'medicine_name_snapshot' => 'Sales Tablet',
                'unit_name_snapshot' => 'Tablet',
                'dosage_instructions' => '1 tablet twice daily',
                'quantity_prescribed' => '10.000000',
                'quantity_dispensed' => '0.000000',
                'notes' => 'Demo prescription line.',
            ]);
        }
    }
}
