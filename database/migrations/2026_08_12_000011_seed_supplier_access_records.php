<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $now = now();
            $supplierPermissionSlugs = [
                'suppliers.view',
                'suppliers.manage',
            ];
            $permissionDefinitions = config('access.permissions', []);

            foreach ($supplierPermissionSlugs as $slug) {
                $definition = $permissionDefinitions[$slug];

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

            $ownerRoleId = DB::table('roles')->where('slug', 'owner')->value('id');

            if (! $ownerRoleId) {
                return;
            }

            foreach ($supplierPermissionSlugs as $slug) {
                $permissionId = DB::table('permissions')->where('slug', $slug)->value('id');

                DB::table('permission_role')->updateOrInsert(
                    [
                        'permission_id' => $permissionId,
                        'role_id' => $ownerRoleId,
                    ],
                    [
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
                ->whereIn('slug', ['suppliers.view', 'suppliers.manage'])
                ->pluck('id');

            DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        });
    }
};
