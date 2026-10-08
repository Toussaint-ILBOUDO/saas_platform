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
            // Session (D8) : l'API cabinet est consommée par Angular sur la même
            // base → authentification par cookie de session (guard web), comme le web.
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Session\Middleware\StartSession::class,
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
            'keduc.web' => \App\Http\Middleware\KeducWebAutorise::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        // Le web KEduc accepte aussi du JSON (réactions/partages) : un rendu
        // d'erreur JSON est servi dès que la requête l'attend. Convention T3.1 :
        // {message, code, erreurs} — messages en français.
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

        $exceptions->render(function (
            \Illuminate\Validation\ValidationException $e,
            \Illuminate\Http\Request $request
        ) {
            return $request->expectsJson()
                ? response()->json([
                    'message' => 'Les données envoyées sont invalides.',
                    'code' => 'VALIDATION',
                    'erreurs' => $e->errors(),
                ], 422)
                : null;
        });

        $exceptions->render(function (
            \Illuminate\Auth\AuthenticationException $e,
            \Illuminate\Http\Request $request
        ) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Non connecté.', 'code' => 'NON_CONNECTE'], 401)
                : null;
        });

        $exceptions->render(function (
            \Illuminate\Auth\Access\AuthorizationException $e,
            \Illuminate\Http\Request $request
        ) {
            return $request->expectsJson()
                ? response()->json([
                    'message' => $e->getMessage() ?: 'Accès refusé.',
                    'code' => 'ACCES_REFUSE',
                ], 403)
                : null;
        });

        $exceptions->render(function (
            \Spatie\Permission\Exceptions\UnauthorizedException $e,
            \Illuminate\Http\Request $request
        ) {
            return $request->expectsJson()
                ? response()->json([
                    'message' => 'Accès refusé : rôle insuffisant.',
                    'code' => 'ACCES_REFUSE',
                ], 403)
                : null;
        });

        $exceptions->render(function (
            \Illuminate\Database\Eloquent\ModelNotFoundException $e,
            \Illuminate\Http\Request $request
        ) {
            return $request->expectsJson()
                ? response()->json([
                    'message' => 'Ressource introuvable.',
                    'code' => 'RESSOURCE_INTROUVABLE',
                ], 404)
                : null;
        });

        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\HttpException $e,
            \Illuminate\Http\Request $request
        ) {
            if (! $request->expectsJson()) {
                return null;
            }

            return match ($e->getStatusCode()) {
                401 => response()->json(['message' => 'Authentification requise.', 'code' => 'NON_AUTHENTIFIE'], 401),
                403 => response()->json(['message' => 'Accès refusé.', 'code' => 'ACCES_REFUSE'], 403),
                404 => response()->json(['message' => 'Introuvable.', 'code' => 'INTROUVABLE'], 404),
                405 => response()->json(['message' => 'Méthode non autorisée.', 'code' => 'METHODE_NON_AUTORISEE'], 405),
                429 => response()->json(['message' => 'Trop de requêtes. Réessayez plus tard.', 'code' => 'TROP_DE_REQUETES'], 429),
                default => null,
            };
        });

        $exceptions->render(function (
            \App\Modules\Pedagogie\Exceptions\ModeleRapportNonMigreException $e,
            \Illuminate\Http\Request $request
        ) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => 'Le modèle de rapport n\'est pas encore présent dans la base de ce cabinet : la migration des cabinets n\'a pas été appliquée. Exécutez \'php artisan tenants:migrate\' depuis backend/ puis rechargez la page.',
                'code' => 'MODELE_RAPPORT_NON_MIGRE',
            ], 500);
        });

        $exceptions->render(function (Throwable $e, \Illuminate\Http\Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            report($e);

            return response()->json([
                'message' => 'Erreur interne du serveur.',
                'code' => 'ERREUR_INTERNE',
            ], 500);
        });
    })->create();

    
