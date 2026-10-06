<?php

use App\Http\Middleware\EnsureAccountOwner;
use App\Http\Middleware\EnsureCompanyPermission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'company.permission' => EnsureCompanyPermission::class,
            'account.owner' => EnsureAccountOwner::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {})
    ->create();