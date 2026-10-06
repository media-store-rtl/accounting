<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $company = $user?->account?->company;
        $entitlement = $company?->subscriptionEntitlement;

        if (! $company || ! $entitlement?->isActive()) {
            return redirect()
                ->route('dashboard')
                ->with('subscription_error', 'برای انجام این عملیات باید اشتراک فعال داشته باشید.');
        }

        return $next($request);
    }
}
