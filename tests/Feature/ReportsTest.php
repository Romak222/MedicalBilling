<?php

namespace Tests\Feature;

use App\Livewire\ReportsIndex;
use App\Models\ControlledMedicineRegisterEntry;
use App\Models\User;
use App\Support\FirstRunSetup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_operational_reports(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());

        $this->actingAs($owner)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Operational Reporting')
            ->assertSee('Gross Sales')
            ->assertSee('Controlled-medicine register');
    }

    public function test_report_period_rejects_reversed_dates(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());

        Livewire::actingAs($owner)
            ->test(ReportsIndex::class)
            ->set('fromDate', '2026-08-20')
            ->set('toDate', '2026-08-19')
            ->call('applyFilters')
            ->assertHasErrors(['toDate']);
    }

    public function test_owner_can_download_date_bounded_controlled_register_csv(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());

        ControlledMedicineRegisterEntry::query()->create([
            'entry_type' => ControlledMedicineRegisterEntry::TYPE_SALE,
            'event_date' => '2026-08-14',
            'quantity_effect' => '2.000000',
            'product_name_snapshot' => 'Report Test Tablet',
            'batch_number_snapshot' => 'REPORT-BATCH-001',
            'patient_name_snapshot' => 'Report Patient',
            'doctor_name_snapshot' => 'Report Doctor',
            'prescription_number_snapshot' => 'RX-REPORT-001',
            'invoice_number_snapshot' => 'SI-REPORT-001',
            'created_by' => $owner->id,
        ]);

        $response = $this->actingAs($owner)->get(route('reports.controlled-medicines.csv', [
            'from' => '2026-08-01',
            'to' => '2026-08-31',
        ]));

        $response
            ->assertOk()
            ->assertDownload('controlled-medicine-register-2026-08-01-2026-08-31.csv');

        $this->assertStringContainsString('Report Test Tablet', $response->streamedContent());
        $this->assertStringContainsString('Report Patient', $response->streamedContent());
    }

    public function test_user_without_reports_permission_is_forbidden(): void
    {
        app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('reports.controlled-medicines.csv', [
                'from' => '2026-08-01',
                'to' => '2026-08-31',
            ]))
            ->assertForbidden();
    }
}
