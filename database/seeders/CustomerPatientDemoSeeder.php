<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerPatientDemoSeeder extends Seeder
{
    public function run(): void
    {
        $actorId = User::query()->orderBy('id')->value('id');

        $customer = Customer::query()->firstOrCreate(
            ['code' => 'CUST-DEMO-001'],
            [
                'name' => 'Demo Family Account',
                'phone' => '9876500001',
                'email' => 'demo-customer@example.test',
                'address_line_1' => 'Demo Street 12',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'postal_code' => '411001',
                'opening_balance' => '0.00',
                'outstanding_balance' => '0.00',
                'loyalty_points' => 20,
                'reminder_consent' => true,
                'whatsapp_consent' => true,
                'sms_consent' => false,
                'notes' => 'Local demo customer entry for workflow checks.',
                'is_active' => true,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]
        );

        Patient::query()->firstOrCreate(
            ['patient_code' => 'PAT-DEMO-001'],
            [
                'customer_id' => $customer->id,
                'full_name' => 'Demo Patient',
                'phone' => '9876500002',
                'email' => 'demo-patient@example.test',
                'date_of_birth' => '1992-08-12',
                'gender' => 'female',
                'primary_doctor_name' => 'Dr Demo',
                'address_line_1' => 'Demo Street 12',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'postal_code' => '411001',
                'allergies' => 'No known allergies',
                'medical_notes' => 'Demo-only record for patient workflow validation.',
                'notes' => 'Local demo patient entry.',
                'reminder_consent' => true,
                'whatsapp_consent' => true,
                'sms_consent' => true,
                'is_active' => true,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]
        );

        $this->call(DoctorPrescriptionDemoSeeder::class);
        $this->call(PrescriptionRefillDemoSeeder::class);
        $this->call(ControlledMedicineDemoSeeder::class);
        $this->call(CashDrawerDemoSeeder::class);
    }
}
