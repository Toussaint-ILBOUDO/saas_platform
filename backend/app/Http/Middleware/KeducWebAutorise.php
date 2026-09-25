<?php

namespace App\Http\Middleware;

use App\Models\ParametresPlateforme;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interrupteur global de délivrance des écrans web KEduc (décision B4, T2.10) :
 * par défaut DÉSACTIVÉ (le web's gelé n'est pas servi aux cabinets) ; le
 * Landlord (paramètres de la plateforme) peut le réactiver.
 * S'applique uniquement aux routes web tenant — jamais à l'API ni aux routes
 * d'impersonation.
 */
class KeducWebAutorise
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) ParametresPlateforme::obtenir(ParametresPlateforme::CLE_ACCES_WEB_KEDUC, false)) {
            abort(404);
        }

        return $next($request);
    }
}