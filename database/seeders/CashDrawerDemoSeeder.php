<?php

namespace Database\Seeders;

use App\Models\CashDrawerEntry;
use App\Models\CashDrawerShift;
use App\Models\User;
use Illuminate\Database\Seeder;

class CashDrawerDemoSeeder extends Seeder
{
    public function run(): void
    {
        $actorId = User::query()->orderBy('id')->value('id');
        if (! $actorId) {
            return;
        }

        $shift = CashDrawerShift::query()->firstOrCreate(
            ['shift_number' => 'CD-DEMO-001'],
            [
                'status' => CashDrawerShift::STATUS_CLOSED,
                'opened_at' => '2026-08-12 09:00:00',
                'closed_at' => '2026-08-12 18:00:00',
                'opening_float' => '500.00',
                'cash_sales_amount' => '250.00',
                'cash_refunds_amount' => '0.00',
                'cash_in_amount' => '25.00',
                'cash_out_amount' => '10.00',
                'expected_closing_cash' => '765.00',
                'counted_closing_cash' => '760.00',
                'variance_amount' => '-5.00',
                'opening_notes' => 'Demo opening float for shift workflow review.',
                'closing_notes' => 'Demo short variance for reconciliation review.',
                'opened_by' => $actorId,
                'closed_by' => $actorId,
            ]
        );

        CashDrawerEntry::query()->firstOrCreate(
            [
                'cash_drawer_shift_id' => $shift->id,
                'entry_type' => CashDrawerEntry::TYPE_CASH_IN,
                'reason' => 'Demo safe transfer',
            ],
            [
                'amount' => '25.00',
                'created_by' => $actorId,
            ]
        );

        CashDrawerEntry::query()->firstOrCreate(
            [
                'cash_drawer_shift_id' => $shift->id,
                'entry_type' => CashDrawerEntry::TYPE_CASH_OUT,
                'reason' => 'Demo petty cash',
            ],
            [
                'amount' => '10.00',
                'created_by' => $actorId,
            ]
        );
    }
}
