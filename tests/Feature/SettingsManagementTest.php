<?php

namespace Tests\Feature;

use App\Livewire\SettingsIndex;
use App\Models\ApplicationSetting;
use App\Models\AuditEvent;
use App\Models\RegisteredPharmacist;
use App\Models\Store;
use App\Models\User;
use App\Support\FirstRunSetup;
use App\Support\SystemStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_and_save_settings_with_audit_event(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());

        $this->actingAs($owner)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Printer configuration');

        Livewire::test(SettingsIndex::class)
            ->set('store.name', 'Updated Medical Store')
            ->set('pharmacist.name', 'Updated Pharmacist')
            ->set('billing.invoice_prefix', 'BILL')
            ->set('printing.receipt_paper_width_mm', '58')
            ->set('printing.receipt_copies', '2')
            ->set('printing.receipt_footer', 'Keep your receipt.')
            ->set('operations.receipt_printer_name', 'EPSON TM-T88')
            ->set('operations.backup_path', 'D:\\MedicalBackups')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Updated Medical Store', Store::query()->firstOrFail()->name);
        $this->assertSame('Updated Pharmacist', RegisteredPharmacist::query()->firstOrFail()->name);
        $this->assertSame('BILL', ApplicationSetting::getValue('billing.invoice_prefix'));
        $this->assertSame(58, ApplicationSetting::getValue('printing.receipt_paper_width_mm'));
        $this->assertSame(2, ApplicationSetting::getValue('printing.receipt_copies'));
        $this->assertSame('EPSON TM-T88', ApplicationSetting::getValue('printing.receipt_printer_name'));
        $this->assertSame('D:\\MedicalBackups', app(SystemStatus::class)->backupPath());
        $this->assertSame(1, AuditEvent::query()->where('action', 'settings.updated')->count());
    }

    public function test_user_without_settings_permission_is_forbidden(): void
    {
        app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.index'))
            ->assertForbidden();
    }

    public function test_invalid_receipt_configuration_is_rejected(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());

        Livewire::actingAs($owner)
            ->test(SettingsIndex::class)
            ->set('printing.receipt_paper_width_mm', '72')
            ->set('printing.receipt_copies', '4')
            ->call('save')
            ->assertHasErrors([
                'printing.receipt_paper_width_mm',
                'printing.receipt_copies',
            ]);
    }
}
