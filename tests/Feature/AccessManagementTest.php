<?php

namespace Tests\Feature;

use App\Livewire\AccessIndex;
use App\Models\AuditEvent;
use App\Models\Role;
use App\Models\User;
use App\Support\AccessDirectory;
use App\Support\FirstRunSetup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class AccessManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_staff_user_with_role_assignment(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $cashier = Role::query()->where('slug', 'cashier')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('access.index'))
            ->assertOk()
            ->assertSee('Users and Roles')
            ->assertSee('Cashier');

        Livewire::test(AccessIndex::class)
            ->set('name', 'Counter Cashier')
            ->set('email', 'cashier@example.test')
            ->set('password', 'CashierPassword123!')
            ->set('password_confirmation', 'CashierPassword123!')
            ->set('selectedRoleIds', [$cashier->id])
            ->call('saveUser')
            ->assertHasNoErrors();

        $staff = User::query()->where('email', 'cashier@example.test')->firstOrFail();

        $this->assertFalse($staff->is_owner);
        $this->assertTrue($staff->is_active);
        $this->assertSame(['cashier'], $staff->roles()->pluck('slug')->all());
        $this->assertSame(1, AuditEvent::query()->where('action', 'access.user.created')->count());
    }

    public function test_disabled_staff_user_cannot_login_and_can_be_restored(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $cashier = Role::query()->where('slug', 'cashier')->firstOrFail();

        $staff = app(AccessDirectory::class)->saveUser(
            null,
            [
                'name' => 'Disabled Cashier',
                'email' => 'disabled@example.test',
                'password' => 'CashierPassword123!',
            ],
            [$cashier->id],
            $owner
        );

        Livewire::actingAs($owner)
            ->test(AccessIndex::class)
            ->call('deactivateUser', $staff->id)
            ->assertHasNoErrors();

        $this->assertFalse($staff->refresh()->is_active);

        auth()->logout();

        $this->post('/login', [
            'email' => 'disabled@example.test',
            'password' => 'CashierPassword123!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame('inactive_account', AuditEvent::query()->latest('id')->firstOrFail()->metadata['failure_reason']);

        Livewire::actingAs($owner)
            ->test(AccessIndex::class)
            ->call('restoreUser', $staff->id)
            ->assertHasNoErrors();

        $this->assertTrue($staff->refresh()->is_active);
        $this->assertSame(1, AuditEvent::query()->where('action', 'access.user.deactivated')->count());
        $this->assertSame(1, AuditEvent::query()->where('action', 'access.user.restored')->count());
    }

    public function test_owner_account_cannot_be_disabled(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());

        $this->expectException(ValidationException::class);

        app(AccessDirectory::class)->deactivate($owner, $owner);
    }

    public function test_user_without_access_management_permission_is_forbidden(): void
    {
        app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('access.index'))
            ->assertForbidden();
    }
}
