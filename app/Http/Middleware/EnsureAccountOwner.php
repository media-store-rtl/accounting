<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isAccountOwner(), 403);

        return $next($request);
    }
}