<?php

use App\Http\Middleware\EnsureAccountOwner;
use App\Http\Middleware\EnsureCompanyPermission;
use App\Http\Middleware\AuditTrailMiddleware;
use App\Http\Middleware\ConsumeApprovedShiftReportMaterials;
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
        $middleware->append(AuditTrailMiddleware::class)
        ->alias([
            'company.permission' => EnsureCompanyPermission::class,
            'account.owner' => EnsureAccountOwner::class,
        ]);

        // This middleware needs the matched route and authenticated web session.
        // Global middleware runs before route matching and StartSession, so it would
        // return early without ever consuming materials after shift-report approval.
        $middleware->web(append: [ConsumeApprovedShiftReportMaterials::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {})
    ->create();
