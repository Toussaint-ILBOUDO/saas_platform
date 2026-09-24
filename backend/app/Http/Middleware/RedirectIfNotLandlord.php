<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Landlord : exige la connexion (guard landlord). Ne réutilise pas le
 * middleware « auth » par défaut (il pointerait vers la route « login »
 * du tenant) : on redirige explicitement vers landlord.login.
 */
class RedirectIfNotLandlord
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth('landlord')->check()) {
            return redirect()->route('landlord.login');
        }

        return $next($request);
    }
}