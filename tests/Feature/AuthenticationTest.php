<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\LoginEvent;
use App\Models\User;
use App\Support\FirstRunSetup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_setup_redirects_guest_status_requests_to_login(): void
    {
        app(FirstRunSetup::class)->complete($this->firstRunPayload());

        $this->get('/status')
            ->assertRedirect(route('login'));
    }

    public function test_login_is_audited_and_allows_owner_to_view_status(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());

        $this->post('/login', [
            'email' => 'owner@example.test',
            'password' => 'StrongPassword123!',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($owner);

        $this->get('/status')
            ->assertOk()
            ->assertSee('System Status');

        $this->assertSame(1, LoginEvent::query()->where('successful', true)->count());
        $this->assertSame(1, AuditEvent::query()->where('action', 'auth.login.succeeded')->count());
    }

    public function test_failed_login_is_audited_without_authentication(): void
    {
        app(FirstRunSetup::class)->complete($this->firstRunPayload());
        auth()->logout();

        $this->post('/login', [
            'email' => 'owner@example.test',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame(1, LoginEvent::query()->where('successful', false)->count());
        $this->assertSame(1, AuditEvent::query()->where('action', 'auth.login.failed')->count());
    }

    public function test_logout_is_audited(): void
    {
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());

        $this->actingAs($owner)
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertSame(1, AuditEvent::query()->where('action', 'auth.logout')->count());
    }

    public function test_user_without_status_permission_is_forbidden(): void
    {
        app(FirstRunSetup::class)->complete($this->firstRunPayload());
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/status')
            ->assertForbidden();
    }
}
