<?php

namespace Tests\Feature;

use App\Livewire\FirstRunWizard;
use App\Models\ApplicationSetting;
use App\Models\AuditEvent;
use App\Models\FirstRunSetupStep;
use App\Models\Permission;
use App\Models\RegisteredPharmacist;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use App\Support\FirstRunSetup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class FirstRunSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_install_redirects_root_to_setup(): void
    {
        $this->get('/')
            ->assertRedirect(route('setup'));
    }

    public function test_setup_page_boots_before_completion(): void
    {
        $this->withoutVite();

        $this->get('/setup')
            ->assertOk()
            ->assertSee('First-run Setup');
    }

    public function test_first_run_wizard_completes_setup(): void
    {
        $this->withoutVite();

        Livewire::test(FirstRunWizard::class)
            ->set('store.code', 'MAIN')
            ->set('store.name', 'Main Medical Store')
            ->set('store.legal_name', 'Main Medical Store Pvt Ltd')
            ->set('store.gstin', 'GSTIN-PENDING')
            ->set('store.pan', 'PAN-PENDING')
            ->set('store.drug_license_number', 'DL-PENDING')
            ->set('store.drug_license_valid_until', '2027-03-31')
            ->set('store.address_line_1', 'Market Road')
            ->set('store.city', 'Pune')
            ->set('store.state', 'Maharashtra')
            ->set('store.postal_code', '411001')
            ->set('pharmacist.name', 'Primary Pharmacist')
            ->set('pharmacist.registration_number', 'REG-PENDING')
            ->set('pharmacist.council_name', 'State Pharmacy Council')
            ->set('pharmacist.license_valid_until', '2027-03-31')
            ->set('billing.invoice_prefix', 'INV')
            ->set('billing.financial_year_starts_on', '2026-04-01')
            ->set('operations.default_printer_name', 'Front Counter Printer')
            ->set('operations.receipt_printer_name', 'Thermal Receipt Printer')
            ->set('operations.backup_path', 'C:\\MedStoreBackups')
            ->set('owner.name', 'Store Owner')
            ->set('owner.email', 'owner@example.test')
            ->set('owner.password', 'StrongPassword123!')
            ->set('owner.password_confirmation', 'StrongPassword123!')
            ->call('complete')
            ->assertHasNoErrors()
            ->assertRedirect(route('status'));

        $owner = User::query()->firstOrFail();

        $this->assertTrue($owner->is_owner);
        $this->assertTrue($owner->created_during_setup);
        $this->assertTrue(Hash::check('StrongPassword123!', $owner->password));
        $this->assertNotSame('StrongPassword123!', $owner->password);
        $this->assertTrue(app(FirstRunSetup::class)->isComplete());
        $this->assertTrue(ApplicationSetting::getValue(FirstRunSetup::COMPLETED_SETTING));
        $this->assertSame(1, Store::query()->count());
        $this->assertSame(1, RegisteredPharmacist::query()->count());
        $this->assertSame(6, FirstRunSetupStep::query()->whereNotNull('completed_at')->count());
        $this->assertTrue($owner->hasRole('owner'));
        $this->assertTrue($owner->hasPermission('system.status.view'));
        $this->assertTrue($owner->hasPermission('suppliers.view'));
        $this->assertTrue($owner->hasPermission('customers.view'));
        $this->assertTrue($owner->hasPermission('patients.view'));
        $this->assertTrue($owner->hasPermission('doctors.view'));
        $this->assertTrue($owner->hasPermission('prescriptions.view'));
        $this->assertTrue($owner->hasPermission('controlled_medicines.view'));
        $this->assertTrue($owner->hasPermission('purchases.view'));
        $this->assertTrue($owner->hasPermission('inventory.view'));
        $this->assertTrue($owner->hasPermission('sales.view'));
        $this->assertSame(1, Role::query()->where('slug', 'owner')->count());
        $this->assertSame(32, Permission::query()->count());
        $this->assertSame(1, AuditEvent::query()->where('action', 'setup.completed')->count());
        $this->assertAuthenticatedAs($owner);
    }

    public function test_database_seeder_does_not_create_public_default_user(): void
    {
        $this->seed();

        $this->assertSame(0, User::query()->count());
    }
}
