<?php

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
                                'role' =>
                                    \Spatie\Permission\Middleware\RoleMiddleware::class,

                                'permission' =>
                                    \Spatie\Permission\Middleware\PermissionMiddleware::class,

                                'role_or_permission' =>
                                    \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,

                                'platform_admin' =>
                                    \App\Http\Middleware\EnsurePlatformAdmin::class,

                                'active_subscription' =>
                                    \App\Http\Middleware\EnsureTenantSubscriptionIsActive::class,

                                'feature' =>
                                    \App\Http\Middleware\EnsureTenantFeatureIsEnabled::class,

                                'verified' =>
                                    \App\Http\Middleware\EnsureEmailVerificationIsRequired::class,

                            ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
