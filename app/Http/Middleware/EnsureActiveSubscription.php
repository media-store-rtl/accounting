<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $subscription = $request->user()?->subscription;

        if ($subscription?->isActive() === true) {
            return $next($request);
        }

        return redirect()
            ->route('dashboard')
            ->with('subscription_required', true);
    }
}
