<?php

namespace App\Support;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AccessControl
{
    public const OWNER_ROLE = 'owner';

    public function syncSystemRecords(): void
    {
        DB::transaction(function (): void {
            $permissions = collect(config('access.permissions', []))
                ->mapWithKeys(function (array $definition, string $slug): array {
                    $permission = Permission::query()->updateOrCreate(
                        ['slug' => $slug],
                        [
                            'name' => $definition['name'],
                            'module' => $definition['module'],
                            'description' => $definition['description'] ?? null,
                            'is_sensitive' => $definition['is_sensitive'] ?? false,
                        ]
                    );

                    return [$slug => $permission];
                });

            foreach (config('access.roles', []) as $slug => $definition) {
                $role = Role::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $definition['name'],
                        'description' => $definition['description'] ?? null,
                        'is_system' => $definition['is_system'] ?? false,
                    ]
                );

                $role->permissions()->sync(
                    collect($definition['permissions'] ?? [])
                        ->map(fn (string $permissionSlug): int => $permissions->get($permissionSlug)->id)
                        ->all()
                );
            }
        });
    }

    public function assignOwnerRole(User $user, ?User $assignedBy = null): void
    {
        $this->syncSystemRecords();

        $ownerRole = Role::query()->where('slug', self::OWNER_ROLE)->firstOrFail();

        $user->roles()->syncWithoutDetaching([
            $ownerRole->id => [
                'assigned_by' => $assignedBy?->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
