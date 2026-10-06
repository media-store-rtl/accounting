<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        $companyId = (int) $request->session()->get('company_id');

        if (! $user || ! $companyId || ! $user->hasCompanyPermission($companyId, $permission)) {
            abort(403, 'You are not authorized to perform this operation.');
        }

        return $next($request);
    }
}
