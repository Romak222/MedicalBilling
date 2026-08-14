<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\FirstRunSetup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(FirstRunSetup $setup): View|RedirectResponse
    {
        if (! $setup->isComplete()) {
            return redirect()->route('setup');
        }

        return view('auth.login');
    }

    public function store(LoginRequest $request, AuditLogger $auditLogger, FirstRunSetup $setup): RedirectResponse
    {
        if (! $setup->isComplete()) {
            return redirect()->route('setup');
        }

        $validated = $request->validated();
        $email = strtolower($validated['email']);
        $throttleKey = Str::transliterate(Str::lower($email).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, config('security.login_max_attempts'))) {
            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Try again later.',
            ]);
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user || ! $user->is_active || ! Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($throttleKey, config('security.login_decay_seconds'));
            $auditLogger->loginAttempt(
                $user,
                $email,
                false,
                $user && ! $user->is_active ? 'inactive_account' : 'invalid_credentials',
                $request
            );

            throw ValidationException::withMessages([
                'email' => 'The provided credentials could not be verified.',
            ]);
        }

        Auth::login($user);
        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        $auditLogger->loginAttempt($user, $email, true, null, $request);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            $auditLogger->record('auth.logout', $user, null, null, $request);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
