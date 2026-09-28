<?php

namespace App\Modules\Public\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Statistiques publiques (P2) : compteurs de la plateforme affichés sur l'accueil
 * (enseignants, élèves, familles, contrats). Mêmes requêtes que HomeController.
 */
class PublicStatsApiController extends Controller
{
    public function index(): JsonResponse
    {
        $counts = DB::selectOne("
            SELECT
                (SELECT COUNT(*) FROM enseignant_profils) as nb_enseignants,
                (SELECT COUNT(*) FROM eleves) as nb_eleves,
                (SELECT COUNT(*) FROM contrat_cours) as nb_contrats,
                (SELECT COUNT(*) FROM parent_profils) as nb_familles
        ");

        return response()->json([
            'data' => [
                'nb_enseignants' => (int) ($counts->nb_enseignants ?? 0),
                'nb_eleves' => (int) ($counts->nb_eleves ?? 0),
                'nb_contrats' => (int) ($counts->nb_contrats ?? 0),
                'nb_familles' => (int) ($counts->nb_familles ?? 0),
            ],
        ]);
    }
}