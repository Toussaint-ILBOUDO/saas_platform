<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CabinetActif
{
    /**
     * Refuse l'accès à un cabinet suspendu (statut ≠ actif).
     * S'exécute après initialisation du tenancy.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (tenancy()->initialized) {
            $status = tenant('status');

            if ($status !== null && $status !== 'actif') {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Cabinet suspendu. Contactez la plateforme.',
                        'code' => 'CABINET_SUSPENDU',
                    ], 403);
                }

                abort(403, 'CABINET_SUSPENDU');
            }
        }

        return $next($request);
    }
}