<?php

namespace App\Http\Middleware;

use App\Support\FirstRunSetup;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFirstRunSetupIsComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app(FirstRunSetup::class)->isComplete()) {
            return redirect()->route('setup');
        }

        return $next($request);
    }
}
