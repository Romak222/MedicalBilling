<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $now = now();

            foreach (['1050', '6000'] as $code) {
                $definition = config('accounting.accounts.'.$code);

                DB::table('accounts')->updateOrInsert(
                    ['code' => $code],
                    [
                        'name' => $definition['name'],
                        'account_type' => $definition['account_type'],
                        'normal_balance' => $definition['normal_balance'],
                        'description' => $definition['description'] ?? null,
                        'is_system' => true,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::table('accounts')
                ->whereIn('code', ['1050', '6000'])
                ->whereNotExists(function ($query): void {
                    $query->select(DB::raw(1))
                        ->from('journal_entry_lines')
                        ->whereColumn('journal_entry_lines.account_id', 'accounts.id');
                })
                ->delete();
        });
    }
};
