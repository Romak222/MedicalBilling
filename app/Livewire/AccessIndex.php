<?php

namespace App\Livewire;

use App\Models\Role;
use App\Models\User;
use App\Support\AccessControl;
use App\Support\AccessDirectory;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AccessIndex extends Component
{
    public string $search = '';

    public string $statusFilter = 'active';

    public ?int $editingUserId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /** @var list<int> */
    public array $selectedRoleIds = [];

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->hasPermission('users.manage') || auth()->user()?->hasPermission('roles.manage'),
            403
        );

        $this->resetUserForm();
    }

    public function editUser(int $userId): void
    {
        abort_unless(auth()->user()?->hasPermission('users.manage'), 403);

        $user = User::query()->with('roles')->findOrFail($userId);
        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->password_confirmation = '';
        $this->selectedRoleIds = $user->is_owner ? [] : $user->roles->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $this->resetValidation();
    }

    public function cancelEdit(): void
    {
        $this->resetUserForm();
        $this->resetValidation();
    }

    public function saveUser(): void
    {
        abort_unless(auth()->user()?->hasPermission('users.manage'), 403);

        $editingUser = $this->editingUserId ? User::query()->findOrFail($this->editingUserId) : null;
        $isOwner = $editingUser?->is_owner === true;

        $rules = [
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($editingUser?->id)],
            'password' => [$editingUser ? 'nullable' : 'required', 'string', 'min:12', 'confirmed'],
            'selectedRoleIds' => [$isOwner ? 'nullable' : 'required', 'array', 'min:1'],
            'selectedRoleIds.*' => [
                'integer',
                Rule::exists('roles', 'id')->where(fn ($query) => $query
                    ->where('is_system', true)
                    ->where('slug', '!=', AccessControl::OWNER_ROLE)),
            ],
        ];

        $validated = $this->validate($rules);

        app(AccessDirectory::class)->saveUser(
            $editingUser,
            [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'] ?? '',
            ],
            array_map('intval', $validated['selectedRoleIds'] ?? []),
            auth()->user()
        );

        $message = $editingUser ? 'User account updated.' : 'Staff account created.';
        $this->resetUserForm();
        session()->flash('status', $message);
    }

    public function deactivateUser(int $userId): void
    {
        abort_unless(auth()->user()?->hasPermission('users.manage'), 403);

        app(AccessDirectory::class)->deactivate(User::query()->findOrFail($userId), auth()->user());
        session()->flash('status', 'User account disabled.');
    }

    public function restoreUser(int $userId): void
    {
        abort_unless(auth()->user()?->hasPermission('users.manage'), 403);

        app(AccessDirectory::class)->restore(User::query()->findOrFail($userId), auth()->user());
        session()->flash('status', 'User account restored.');
    }

    public function render()
    {
        return view('livewire.access-index', [
            'users' => $this->usersQuery()->with('roles')->latest()->limit(100)->get(),
            'roles' => Role::query()->where('slug', '!=', AccessControl::OWNER_ROLE)->with('permissions')->orderBy('name')->get(),
            'stats' => [
                'total' => User::query()->count(),
                'active' => User::query()->where('is_active', true)->count(),
                'inactive' => User::query()->where('is_active', false)->count(),
                'staff' => User::query()->where('is_owner', false)->count(),
            ],
            'editingUser' => $this->editingUserId ? User::query()->find($this->editingUserId) : null,
            'canManageUsers' => auth()->user()?->hasPermission('users.manage') ?? false,
        ]);
    }

    private function resetUserForm(): void
    {
        $this->editingUserId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->selectedRoleIds = [
            (int) Role::query()->where('slug', 'cashier')->value('id'),
        ];
    }

    private function usersQuery()
    {
        return User::query()
            ->when($this->statusFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($this->search !== '', function ($query): void {
                $search = '%'.$this->search.'%';

                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhereHas('roles', fn ($query) => $query->where('name', 'like', $search));
                });
            });
    }
}
