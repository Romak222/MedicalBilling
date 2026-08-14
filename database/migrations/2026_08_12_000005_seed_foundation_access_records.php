<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $now = now();
            $permissionIds = [];

            foreach (config('access.permissions', []) as $slug => $definition) {
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

                $permissionIds[$slug] = DB::table('permissions')->where('slug', $slug)->value('id');
            }

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
                    DB::table('permission_role')->updateOrInsert(
                        [
                            'permission_id' => $permissionIds[$permissionSlug],
                            'role_id' => $roleId,
                        ],
                        [
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                }
            }

            $ownerRoleId = DB::table('roles')->where('slug', 'owner')->value('id');

            DB::table('users')
                ->where('is_owner', true)
                ->orderBy('id')
                ->pluck('id')
                ->each(function (int $userId) use ($ownerRoleId, $now): void {
                    DB::table('role_user')->updateOrInsert(
                        [
                            'role_id' => $ownerRoleId,
                            'user_id' => $userId,
                        ],
                        [
                            'assigned_by' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                });
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $roleSlugs = array_keys(config('access.roles', []));
            $permissionSlugs = array_keys(config('access.permissions', []));

            DB::table('role_user')
                ->whereIn('role_id', DB::table('roles')->whereIn('slug', $roleSlugs)->pluck('id'))
                ->delete();

            DB::table('permission_role')
                ->whereIn('role_id', DB::table('roles')->whereIn('slug', $roleSlugs)->pluck('id'))
                ->orWhereIn('permission_id', DB::table('permissions')->whereIn('slug', $permissionSlugs)->pluck('id'))
                ->delete();

            DB::table('roles')->whereIn('slug', $roleSlugs)->delete();
            DB::table('permissions')->whereIn('slug', $permissionSlugs)->delete();
        });
    }
};
