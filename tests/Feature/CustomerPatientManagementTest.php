<?php

namespace Tests\Feature;

use App\Livewire\CustomerForm;
use App\Livewire\CustomerIndex;
use App\Livewire\PatientForm;
use App\Livewire\PatientIndex;
use App\Models\AuditEvent;
use App\Models\Customer;
use App\Models\Patient;
use App\Models\User;
use App\Support\FirstRunSetup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerPatientManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_setup_redirects_guest_customer_and_patient_requests_to_login(): void
    {
        app(FirstRunSetup::class)->complete($this->firstRunPayload());

        $this->get('/customers')
            ->assertRedirect(route('login'));

        $this->get('/patients')
            ->assertRedirect(route('login'));
    }

    public function test_owner_can_view_customer_and_patient_workspaces(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());

        $this->actingAs($owner)
            ->get('/customers')
            ->assertOk()
            ->assertSee('Customers')
            ->assertSee('Add New Customer');

        $this->actingAs($owner)
            ->get('/patients')
            ->assertOk()
            ->assertSee('Patients')
            ->assertSee('Add New Patient');
    }

    public function test_owner_can_create_customer_and_linked_patient_records(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);

        Livewire::test(CustomerForm::class)
            ->set('customer.name', 'Metro Clinic Account')
            ->set('customer.code', 'cust-001')
            ->set('customer.phone', '9876543210')
            ->set('customer.email', 'metro@example.test')
            ->set('customer.gstin', 'gstin-cust-001')
            ->set('customer.address_line_1', 'Station Road')
            ->set('customer.city', 'Pune')
            ->set('customer.state', 'Maharashtra')
            ->set('customer.postal_code', '411001')
            ->set('customer.opening_balance', '250.00')
            ->set('customer.credit_limit', '10000.00')
            ->set('customer.outstanding_balance', '250.00')
            ->set('customer.loyalty_points', '45')
            ->set('customer.reminder_consent', true)
            ->set('customer.whatsapp_consent', true)
            ->set('customer.notes', 'Monthly account customer.')
            ->call('save')
            ->assertHasNoErrors();

        $customer = Customer::query()->firstOrFail();

        $this->assertSame('Metro Clinic Account', $customer->name);
        $this->assertSame('CUST-001', $customer->code);
        $this->assertSame('GSTIN-CUST-001', $customer->gstin);
        $this->assertSame(45, $customer->loyalty_points);
        $this->assertTrue($customer->reminder_consent);
        $this->assertSame(1, AuditEvent::query()->where('action', 'customer.created')->count());

        Livewire::test(PatientForm::class)
            ->set('patient.customer_id', (string) $customer->id)
            ->set('patient.full_name', 'Riya Patient')
            ->set('patient.patient_code', 'pt-001')
            ->set('patient.phone', '9123456780')
            ->set('patient.email', 'riya@example.test')
            ->set('patient.date_of_birth', '1995-04-18')
            ->set('patient.gender', 'female')
            ->set('patient.primary_doctor_name', 'Dr Mehta')
            ->set('patient.address_line_1', 'Station Road')
            ->set('patient.city', 'Pune')
            ->set('patient.state', 'Maharashtra')
            ->set('patient.postal_code', '411001')
            ->set('patient.allergies', 'Penicillin')
            ->set('patient.medical_notes', 'Keep inhaler history visible.')
            ->set('patient.reminder_consent', true)
            ->set('patient.sms_consent', true)
            ->call('save')
            ->assertHasNoErrors();

        $patient = Patient::query()->with('customer')->firstOrFail();

        $this->assertSame('Riya Patient', $patient->full_name);
        $this->assertSame('PT-001', $patient->patient_code);
        $this->assertSame($customer->id, $patient->customer_id);
        $this->assertSame('Metro Clinic Account', $patient->customer?->name);
        $this->assertSame(1, AuditEvent::query()->where('action', 'patient.created')->count());

        $this->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('Metro Clinic Account')
            ->assertSee('Riya Patient');

        $this->get(route('patients.show', $patient))
            ->assertOk()
            ->assertSee('Patient Detail')
            ->assertSee('Dr Mehta')
            ->assertSee('Penicillin');
    }

    public function test_owner_can_edit_delete_and_restore_customer_and_patient(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $this->actingAs($owner);

        $customer = Customer::query()->create([
            'name' => 'Original Customer',
            'code' => 'CUST-EDIT',
            'opening_balance' => '0.00',
            'outstanding_balance' => '0.00',
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $patient = Patient::query()->create([
            'customer_id' => $customer->id,
            'full_name' => 'Original Patient',
            'patient_code' => 'PAT-EDIT',
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        Livewire::test(CustomerForm::class, ['record' => $customer])
            ->set('customer.name', 'Updated Customer')
            ->set('customer.credit_limit', '5000.00')
            ->set('customer.outstanding_balance', '350.00')
            ->call('save')
            ->assertHasNoErrors();

        Livewire::test(PatientForm::class, ['record' => $patient])
            ->set('patient.full_name', 'Updated Patient')
            ->set('patient.primary_doctor_name', 'Dr Shah')
            ->set('patient.allergies', 'Sulfa')
            ->call('save')
            ->assertHasNoErrors();

        $customer->refresh();
        $patient->refresh();

        $this->assertSame('Updated Customer', $customer->name);
        $this->assertSame('350.00', $customer->outstanding_balance);
        $this->assertSame('Updated Patient', $patient->full_name);
        $this->assertSame('Dr Shah', $patient->primary_doctor_name);
        $this->assertSame(1, AuditEvent::query()->where('action', 'customer.updated')->count());
        $this->assertSame(1, AuditEvent::query()->where('action', 'patient.updated')->count());

        Livewire::test(CustomerIndex::class)
            ->call('deactivateCustomer', $customer->id);

        Livewire::test(PatientIndex::class)
            ->call('deactivatePatient', $patient->id);

        $this->assertFalse($customer->fresh()->is_active);
        $this->assertFalse($patient->fresh()->is_active);
        $this->assertSame(1, AuditEvent::query()->where('action', 'customer.deactivated')->count());
        $this->assertSame(1, AuditEvent::query()->where('action', 'patient.deactivated')->count());

        Livewire::test(CustomerIndex::class)
            ->call('restoreCustomer', $customer->id);

        Livewire::test(PatientIndex::class)
            ->call('restorePatient', $patient->id);

        $this->assertTrue($customer->fresh()->is_active);
        $this->assertTrue($patient->fresh()->is_active);
        $this->assertSame(1, AuditEvent::query()->where('action', 'customer.restored')->count());
        $this->assertSame(1, AuditEvent::query()->where('action', 'patient.restored')->count());
    }

    public function test_user_without_customer_or_patient_permissions_is_forbidden(): void
    {
        app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/customers')
            ->assertForbidden();

        $this->actingAs($user)
            ->get('/patients')
            ->assertForbidden();
    }
}
