<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/landlord.php',
            ],
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(append: [
            \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
            \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
            'cabinet.actif',
        ]);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'cabinet.actif' => \App\Http\Middleware\CabinetActif::class,
            'landlord.auth' => \App\Http\Middleware\RedirectIfNotLandlord::class,
            'landlord.guest' => \App\Http\Middleware\RedirectIfLandlord::class,
            'central.domain' => \App\Http\Middleware\EnsureCentralDomain::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (
            \Stancl\Tenancy\Contracts\TenantCouldNotBeIdentifiedException $e,
            \Illuminate\Http\Request $request
        ) {
            return $request->expectsJson()
                ? response()->json([
                    'message' => 'Cabinet introuvable.',
                    'code' => 'CABINET_INCONNU',
                ], 404)
                : abort(404);
        });
    })->create();

    
