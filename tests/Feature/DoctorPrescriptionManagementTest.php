<?php

namespace Tests\Feature;

use App\Livewire\DoctorForm;
use App\Livewire\DoctorIndex;
use App\Livewire\PrescriptionForm;
use App\Livewire\PrescriptionIndex;
use App\Models\AuditEvent;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Product;
use App\Models\User;
use App\Support\FirstRunSetup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DoctorPrescriptionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_doctor_and_prescription_workspaces(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());

        $this->actingAs($owner)
            ->get('/doctors')
            ->assertOk()
            ->assertSee('Doctors')
            ->assertSee('Add New Doctor');

        $this->actingAs($owner)
            ->get('/prescriptions')
            ->assertOk()
            ->assertSee('Prescriptions')
            ->assertSee('Add New Prescription');
    }

    public function test_owner_can_create_doctor_and_prescription_records(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);

        $patient = Patient::query()->create([
            'full_name' => 'RX Patient',
            'patient_code' => 'RX-PAT-001',
            'phone' => '9000000010',
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $product = Product::query()->create([
            'name' => 'Prescription Capsule',
            'sku' => 'RX-CAP-001',
            'prescription_required' => true,
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $product->units()->create([
            'unit_name' => 'Capsule',
            'unit_code' => 'CAP',
            'conversion_factor' => '1',
            'is_base' => true,
            'sellable' => true,
            'purchasable' => true,
        ]);

        Livewire::test(DoctorForm::class)
            ->set('doctor.name', 'Dr Archana')
            ->set('doctor.registration_number', 'doc-rx-001')
            ->set('doctor.specialization', 'Physician')
            ->set('doctor.clinic_name', 'Care Clinic')
            ->set('doctor.phone', '9000000011')
            ->call('save')
            ->assertHasNoErrors();

        $doctor = Doctor::query()->firstOrFail();

        Livewire::test(PrescriptionForm::class)
            ->set('prescription.prescription_number', 'rx-test-001')
            ->set('prescription.patient_id', (string) $patient->id)
            ->set('prescription.doctor_id', (string) $doctor->id)
            ->set('prescription.prescription_date', '2026-08-12')
            ->set('items.0.product_id', (string) $product->id)
            ->call('useProduct', 0)
            ->set('items.0.dosage_instructions', '1 capsule after food')
            ->set('items.0.quantity_prescribed', '6.000000')
            ->call('save')
            ->assertHasNoErrors();

        $prescription = Prescription::query()->with(['doctor', 'patient', 'items'])->firstOrFail();

        $this->assertSame('DOC-RX-001', $doctor->registration_number);
        $this->assertSame('RX-TEST-001', $prescription->prescription_number);
        $this->assertSame($doctor->id, $prescription->doctor_id);
        $this->assertSame($patient->id, $prescription->patient_id);
        $this->assertSame(1, $prescription->items->count());
        $this->assertSame('6.000000', $prescription->items->first()->quantity_prescribed);
        $this->assertSame(1, AuditEvent::query()->where('action', 'doctor.created')->count());
        $this->assertSame(1, AuditEvent::query()->where('action', 'prescription.created')->count());

        $this->get(route('doctors.show', $doctor))
            ->assertOk()
            ->assertSee('Dr Archana')
            ->assertSee('RX-TEST-001');

        $this->get(route('prescriptions.show', $prescription))
            ->assertOk()
            ->assertSee('Prescription Detail')
            ->assertSee('Prescription Capsule');
    }

    public function test_owner_can_edit_archive_and_restore_doctor_and_prescription(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);

        $doctor = Doctor::query()->create([
            'name' => 'Original Doctor',
            'registration_number' => 'DOC-EDIT-001',
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $patient = Patient::query()->create([
            'full_name' => 'Patient Edit',
            'patient_code' => 'PAT-EDIT-002',
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $prescription = Prescription::query()->create([
            'prescription_number' => 'RX-EDIT-001',
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'patient_name_snapshot' => $patient->full_name,
            'doctor_name_snapshot' => $doctor->name,
            'prescription_date' => '2026-08-12',
            'status' => Prescription::STATUS_OPEN,
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $prescription->items()->create([
            'medicine_name_snapshot' => 'Manual RX',
            'quantity_prescribed' => '2.000000',
            'quantity_dispensed' => '0.000000',
        ]);

        Livewire::test(DoctorForm::class, ['record' => $doctor])
            ->set('doctor.name', 'Updated Doctor')
            ->set('doctor.specialization', 'Cardiology')
            ->call('save')
            ->assertHasNoErrors();

        Livewire::test(PrescriptionForm::class, ['record' => $prescription])
            ->set('prescription.pharmacist_notes', 'Verified by pharmacist.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Updated Doctor', $doctor->fresh()->name);
        $this->assertSame('Verified by pharmacist.', $prescription->fresh()->pharmacist_notes);
        $this->assertSame(1, AuditEvent::query()->where('action', 'doctor.updated')->count());
        $this->assertSame(1, AuditEvent::query()->where('action', 'prescription.updated')->count());

        Livewire::test(DoctorIndex::class)
            ->call('deactivateDoctor', $doctor->id);

        Livewire::test(PrescriptionIndex::class)
            ->call('archivePrescription', $prescription->id);

        $this->assertFalse($doctor->fresh()->is_active);
        $this->assertFalse($prescription->fresh()->is_active);

        Livewire::test(DoctorIndex::class)
            ->call('restoreDoctor', $doctor->id);

        Livewire::test(PrescriptionIndex::class)
            ->call('restorePrescription', $prescription->id);

        $this->assertTrue($doctor->fresh()->is_active);
        $this->assertTrue($prescription->fresh()->is_active);
    }

    public function test_user_without_doctor_or_prescription_permissions_is_forbidden(): void
    {
        app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/doctors')
            ->assertForbidden();

        $this->actingAs($user)
            ->get('/prescriptions')
            ->assertForbidden();
    }
}
