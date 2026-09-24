<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Landlord : redirige vers le tableau de bord si déjà authentifié,
 * protège la page de connexion côté domaine central.
 */
class RedirectIfLandlord
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth('landlord')->check()) {
            return redirect()->route('landlord.dashboard');
        }

        return $next($request);
    }
}