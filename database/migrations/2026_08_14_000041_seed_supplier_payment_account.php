<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $definition = config('accounting.accounts.1060');

        DB::table('accounts')->updateOrInsert(
            ['code' => '1060'],
            [
                'name' => $definition['name'],
                'account_type' => $definition['account_type'],
                'normal_balance' => $definition['normal_balance'],
                'description' => $definition['description'] ?? null,
                'is_system' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('accounts')
            ->where('code', '1060')
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('journal_entry_lines')
                    ->whereColumn('journal_entry_lines.account_id', 'accounts.id');
            })
            ->delete();
    }
};
