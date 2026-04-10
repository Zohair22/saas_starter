<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Modules\Billing\Http\Middleware\EnsureFeatureLimit;
use Modules\Billing\Http\Middleware\EnsureTenantApiRateLimit;
use Modules\Tenant\Http\Middleware\EnsureTenantMembership;
use Modules\Tenant\Http\Middleware\IdentifyTenant;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(
        channels: __DIR__.'/../routes/channels.php',
        attributes: ['middleware' => ['auth:sanctum']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => IdentifyTenant::class,
            'tenant.member' => EnsureTenantMembership::class,
            'feature.limit' => EnsureFeatureLimit::class,
            'tenant.api.rate' => EnsureTenantApiRateLimit::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
