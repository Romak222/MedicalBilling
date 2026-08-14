<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $now = now();
            $slugs = ['inventory.adjust'];

            foreach ($slugs as $slug) {
                $definition = config('access.permissions', [])[$slug];

                DB::table('permissions')->updateOrInsert(
                    ['slug' => $slug],
                    [
                        'name' => $definition['name'],
                        'module' => $definition['module'],
                        'description' => $definition['description'] ?? null,
                        'is_sensitive' => $definition['is_sensitive'] ?? true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            $permissionId = DB::table('permissions')->where('slug', 'inventory.adjust')->value('id');

            foreach (['owner', 'manager'] as $roleSlug) {
                $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');

                if ($roleId && $permissionId) {
                    DB::table('permission_role')->updateOrInsert(
                        ['permission_id' => $permissionId, 'role_id' => $roleId],
                        ['created_at' => $now, 'updated_at' => $now]
                    );
                }
            }
        });
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('slug', 'inventory.adjust')->value('id');

        if ($permissionId) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
