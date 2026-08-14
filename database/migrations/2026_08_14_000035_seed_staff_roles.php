<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $now = now();
            $permissionIds = DB::table('permissions')->pluck('id', 'slug');

            foreach (config('access.roles', []) as $slug => $definition) {
                DB::table('roles')->updateOrInsert(
                    ['slug' => $slug],
                    [
                        'name' => $definition['name'],
                        'description' => $definition['description'] ?? null,
                        'is_system' => $definition['is_system'] ?? false,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );

                $roleId = DB::table('roles')->where('slug', $slug)->value('id');

                foreach ($definition['permissions'] ?? [] as $permissionSlug) {
                    $permissionId = $permissionIds->get($permissionSlug);

                    if (! $permissionId) {
                        continue;
                    }

                    DB::table('permission_role')->updateOrInsert(
                        [
                            'permission_id' => $permissionId,
                            'role_id' => $roleId,
                        ],
                        [
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                }
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $roleIds = DB::table('roles')
                ->whereIn('slug', ['manager', 'pharmacist', 'cashier'])
                ->pluck('id');

            DB::table('role_user')->whereIn('role_id', $roleIds)->delete();
            DB::table('permission_role')->whereIn('role_id', $roleIds)->delete();
            DB::table('roles')->whereIn('id', $roleIds)->delete();
        });
    }
};
