<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $now = now();

            foreach (['accounting.view', 'accounting.manage'] as $slug) {
                $definition = config('access.permissions', [])[$slug];

                DB::table('permissions')->updateOrInsert(
                    ['slug' => $slug],
                    [
                        'name' => $definition['name'],
                        'module' => $definition['module'],
                        'description' => $definition['description'] ?? null,
                        'is_sensitive' => $definition['is_sensitive'] ?? false,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            $permissionIds = DB::table('permissions')
                ->whereIn('slug', ['accounting.view', 'accounting.manage'])
                ->pluck('id', 'slug');

            foreach (['owner', 'manager'] as $roleSlug) {
                $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');

                if (! $roleId) {
                    continue;
                }

                foreach ($permissionIds as $permissionId) {
                    DB::table('permission_role')->updateOrInsert(
                        ['permission_id' => $permissionId, 'role_id' => $roleId],
                        ['created_at' => $now, 'updated_at' => $now]
                    );
                }
            }

            foreach (config('accounting.accounts', []) as $code => $definition) {
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
            $permissionIds = DB::table('permissions')
                ->whereIn('slug', ['accounting.view', 'accounting.manage'])
                ->pluck('id');

            DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
            DB::table('accounts')
                ->whereIn('code', array_keys(config('accounting.accounts', [])))
                ->whereNotExists(function ($query): void {
                    $query->select(DB::raw(1))
                        ->from('journal_entry_lines')
                        ->whereColumn('journal_entry_lines.account_id', 'accounts.id');
                })
                ->delete();
        });
    }
};
