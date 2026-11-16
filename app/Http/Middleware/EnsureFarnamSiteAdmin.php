<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFarnamSiteAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $siteAdminId = (int) config('services.farnam.site_admin_user_id');

        abort_unless(
            $siteAdminId > 0
            && $user !== null
            && $user->isAccountOwner()
            && (int) $user->getKey() === $siteAdminId,
            403
        );

        return $next($request);
    }
}
