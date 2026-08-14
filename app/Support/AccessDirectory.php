<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccessDirectory
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $roleIds
     */
    public function saveUser(?User $user, array $attributes, array $roleIds, User $actor): User
    {
        return DB::transaction(function () use ($user, $attributes, $roleIds, $actor): User {
            $isNew = ! $user;

            if ($isNew) {
                $user = User::query()->create([
                    'name' => $attributes['name'],
                    'email' => strtolower($attributes['email']),
                    'password' => $attributes['password'],
                    'is_owner' => false,
                    'created_during_setup' => false,
                    'is_active' => true,
                ]);
            } else {
                $user->update([
                    'name' => $attributes['name'],
                    'email' => strtolower($attributes['email']),
                    ...($attributes['password'] ? ['password' => $attributes['password']] : []),
                ]);
            }

            if ($user->is_owner) {
                $roles = $user->roles()->where('slug', 'owner')->get();
            } else {
                $roles = Role::query()
                    ->whereIn('id', $roleIds)
                    ->where('slug', '!=', AccessControl::OWNER_ROLE)
                    ->get();

                if ($roles->isEmpty()) {
                    throw ValidationException::withMessages([
                        'selectedRoleIds' => 'Assign at least one non-owner role to this staff account.',
                    ]);
                }

                $pivot = $roles->mapWithKeys(fn (Role $role): array => [
                    $role->id => [
                        'assigned_by' => $actor->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ])->all();

                $user->roles()->sync($pivot);
            }

            app(AuditLogger::class)->record(
                $isNew ? 'access.user.created' : 'access.user.updated',
                $actor,
                $user,
                [
                    'email' => $user->email,
                    'role_slugs' => $roles->pluck('slug')->values()->all(),
                    'is_active' => $user->is_active,
                ]
            );

            return $user->refresh()->load('roles');
        });
    }

    public function deactivate(User $user, User $actor): User
    {
        if ($user->is_owner) {
            throw ValidationException::withMessages([
                'user' => 'The owner account cannot be disabled.',
            ]);
        }

        if ($user->is($actor)) {
            throw ValidationException::withMessages([
                'user' => 'You cannot disable the account you are currently using.',
            ]);
        }

        return DB::transaction(function () use ($user, $actor): User {
            $user->update(['is_active' => false]);

            app(AuditLogger::class)->record(
                'access.user.deactivated',
                $actor,
                $user,
                ['email' => $user->email]
            );

            return $user->refresh();
        });
    }

    public function restore(User $user, User $actor): User
    {
        return DB::transaction(function () use ($user, $actor): User {
            $user->update(['is_active' => true]);

            app(AuditLogger::class)->record(
                'access.user.restored',
                $actor,
                $user,
                ['email' => $user->email]
            );

            return $user->refresh();
        });
    }
}
