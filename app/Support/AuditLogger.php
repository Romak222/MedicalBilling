<?php

namespace App\Support;

use App\Models\AuditEvent;
use App\Models\LoginEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function record(
        string $action,
        ?User $actor = null,
        ?Model $subject = null,
        ?array $metadata = null,
        ?Request $request = null
    ): AuditEvent {
        return AuditEvent::query()->create([
            'action' => $action,
            'actor_user_id' => $actor?->id,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->getKey(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }

    public function loginAttempt(?User $user, string $email, bool $successful, ?string $failureReason, Request $request): LoginEvent
    {
        $loginEvent = LoginEvent::query()->create([
            'user_id' => $user?->id,
            'email' => $email,
            'successful' => $successful,
            'failure_reason' => $failureReason,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'occurred_at' => now(),
        ]);

        $this->record(
            $successful ? 'auth.login.succeeded' : 'auth.login.failed',
            $user,
            $loginEvent,
            ['email' => $email, 'failure_reason' => $failureReason],
            $request
        );

        return $loginEvent;
    }
}
