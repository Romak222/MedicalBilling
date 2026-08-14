<x-app-shell page-title="Access" section-label="Users and Roles">
    <x-slot:actions>
        <a href="{{ route('settings.index') }}" class="btn-secondary">Settings</a>
        <button type="submit" form="user-form" class="btn-primary">{{ $editingUser ? 'Save User' : 'Create Staff User' }}</button>
    </x-slot>

    <div class="space-y-5">
        @if (session('status'))
            <div class="surface-panel border-medical-100 bg-medical-50 px-4 py-3 text-sm font-semibold text-medical-800">{{ session('status') }}</div>
        @endif

        <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <div class="metric-tile border-t-4 border-t-medical-600"><p class="metric-label">Total Users</p><p class="mt-2 text-3xl font-semibold text-medical-700">{{ $stats['total'] }}</p></div>
            <div class="metric-tile border-t-4 border-t-care-600"><p class="metric-label">Active</p><p class="mt-2 text-3xl font-semibold text-care-700">{{ $stats['active'] }}</p></div>
            <div class="metric-tile border-t-4 border-t-alert-500"><p class="metric-label">Disabled</p><p class="mt-2 text-3xl font-semibold text-alert-700">{{ $stats['inactive'] }}</p></div>
            <div class="metric-tile border-t-4 border-t-pharma-600"><p class="metric-label">Staff Accounts</p><p class="mt-2 text-3xl font-semibold text-pharma-700">{{ $stats['staff'] }}</p></div>
        </section>

        @if ($canManageUsers)
            <section class="surface-panel p-5">
                <div class="border-b border-slate-200 pb-4">
                    <p class="section-kicker">Staff account</p>
                    <h2 class="mt-1 text-lg font-semibold text-ink-950">{{ $editingUser ? 'Edit user account' : 'Create staff user' }}</h2>
                    <p class="mt-1 text-sm text-slate-600">Passwords require at least 12 characters. The owner account remains protected.</p>
                </div>
                <form id="user-form" wire:submit="saveUser" class="mt-5 space-y-5">
                    <div class="grid gap-4 lg:grid-cols-3">
                        <label class="block"><span class="field-label">Name</span><input wire:model="name" type="text" class="field-control mt-1">@error('name') <span class="field-error">{{ $message }}</span> @enderror</label>
                        <label class="block"><span class="field-label">Email</span><input wire:model="email" type="email" class="field-control mt-1">@error('email') <span class="field-error">{{ $message }}</span> @enderror</label>
                        <label class="block"><span class="field-label">{{ $editingUser ? 'New password (optional)' : 'Password' }}</span><input wire:model="password" type="password" class="field-control mt-1" autocomplete="new-password">@error('password') <span class="field-error">{{ $message }}</span> @enderror</label>
                    </div>

                    <div>
                        <p class="field-label">{{ $editingUser?->is_owner ? 'Owner role' : 'Assigned role(s)' }}</p>
                        @if ($editingUser?->is_owner)
                            <div class="mt-2 rounded-lg border border-medical-100 bg-medical-50 px-4 py-3 text-sm font-semibold text-medical-800">Owner role is fixed and cannot be removed.</div>
                        @else
                            <div class="mt-2 grid gap-3 md:grid-cols-3">
                                @foreach ($roles as $role)
                                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-300 bg-white p-3 transition hover:border-medical-500 has-[:checked]:border-medical-600 has-[:checked]:bg-medical-50">
                                        <input wire:model="selectedRoleIds" type="checkbox" value="{{ $role->id }}" class="mt-1 h-4 w-4 rounded border-slate-300 text-medical-600 focus:ring-medical-100">
                                        <span><span class="block text-sm font-semibold text-ink-950">{{ $role->name }}</span><span class="mt-1 block text-xs leading-5 text-slate-600">{{ $role->description }}</span></span>
                                    </label>
                                @endforeach
                            </div>
                            @error('selectedRoleIds') <span class="field-error">{{ $message }}</span> @enderror
                            @error('selectedRoleIds.*') <span class="field-error">{{ $message }}</span> @enderror
                        @endif
                    </div>

                    <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200 pt-4">
                        @if ($editingUser)
                            <button type="button" wire:click="cancelEdit" class="btn-secondary">Cancel Edit</button>
                        @endif
                        <button type="submit" class="btn-primary">{{ $editingUser ? 'Save User' : 'Create Staff User' }}</button>
                    </div>
                </form>
            </section>
        @endif

        <section class="surface-panel overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-slate-200/80 bg-white p-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="inline-flex w-fit flex-wrap gap-1 rounded-lg bg-slate-100 p-1">
                    @foreach (['active' => 'Active', 'inactive' => 'Disabled', 'all' => 'All Users'] as $value => $label)
                        <button type="button" wire:click="$set('statusFilter', '{{ $value }}')" class="rounded-md px-3 py-2 text-sm font-semibold transition {{ $statusFilter === $value ? 'bg-white text-medical-800 shadow-sm' : 'text-slate-600 hover:bg-white/70 hover:text-ink-900' }}">{{ $label }}</button>
                    @endforeach
                </div>
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search name, email, or role" class="field-control xl:max-w-md">
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="table-header"><tr><th class="px-4 py-3">User</th><th class="px-4 py-3">Roles</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Created</th><th class="px-4 py-3 text-right">Actions</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($users as $user)
                            <tr class="transition hover:bg-care-50/60">
                                <td class="px-4 py-3 align-top"><p class="font-semibold text-ink-950">{{ $user->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $user->email }}</p></td>
                                <td class="px-4 py-3 align-top"><div class="flex flex-wrap gap-1">@foreach ($user->roles as $role)<span class="badge bg-slate-100 text-slate-700">{{ $role->name }}</span>@endforeach</div></td>
                                <td class="px-4 py-3 align-top"><span class="badge {{ $user->is_active ? 'bg-medical-50 text-medical-700' : 'bg-alert-50 text-alert-700' }}">{{ $user->is_active ? 'Active' : 'Disabled' }}</span></td>
                                <td class="px-4 py-3 align-top text-slate-700">{{ $user->created_at?->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-right align-top">
                                    @if ($canManageUsers)
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <button type="button" wire:click="editUser({{ $user->id }})" class="btn-secondary px-3 py-2 text-xs">Edit</button>
                                            @if (! $user->is_owner && $user->is_active)
                                                <button type="button" wire:click="deactivateUser({{ $user->id }})" onclick="return confirm('Disable this user account?')" class="rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">Disable</button>
                                            @elseif (! $user->is_owner && ! $user->is_active)
                                                <button type="button" wire:click="restoreUser({{ $user->id }})" class="btn-secondary px-3 py-2 text-xs">Restore</button>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-500">View only</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-12 text-center text-sm font-medium text-slate-500">No users found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="surface-panel p-5">
            <div class="border-b border-slate-200 pb-4"><p class="section-kicker">Role catalogue</p><h2 class="mt-1 text-lg font-semibold text-ink-950">Built-in staff roles</h2><p class="mt-1 text-sm text-slate-600">Role definitions are controlled by the application and assignment is audited per user.</p></div>
            <div class="mt-5 grid gap-4 lg:grid-cols-3">
                @foreach ($roles as $role)
                    <article class="rounded-lg border border-slate-300 bg-white p-4">
                        <h3 class="font-semibold text-ink-950">{{ $role->name }}</h3>
                        <p class="mt-1 text-sm leading-6 text-slate-600">{{ $role->description }}</p>
                        <div class="mt-4 flex flex-wrap gap-1">@foreach ($role->permissions as $permission)<span class="badge bg-slate-100 text-slate-600">{{ $permission->name }}</span>@endforeach</div>
                    </article>
                @endforeach
            </div>
        </section>
    </div>
</x-app-shell>
