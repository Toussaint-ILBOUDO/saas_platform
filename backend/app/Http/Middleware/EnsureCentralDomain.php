<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Landlord : la console d'administration n'est accessible que sur les
 * domaines centraux (D-006, ex. admin.localhost). Sur un domaine cabinet,
 * tout /admin* du Landlord doit rester invisible → 404.
 */
class EnsureCentralDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->getHost(), config('tenancy.central_domains'), true)) {
            abort(404);
        }

        return $next($request);
    }
}