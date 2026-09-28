<?php

namespace App\Modules\Public\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Référentiels publics (P2) : les options des formulaires publics
 * (type de cours, classes, matières) puisées dans les tables du tenant.
 */
class PublicReferencesApiController extends Controller
{
    public function index(): JsonResponse
    {
        $typeCours = DB::table('type_cours')
            ->where('actif', true)
            ->orderBy('libelle')
            ->get(['id', 'libelle', 'code'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'libelle' => $row->libelle,
                'code' => $row->code,
            ]);

        $classes = DB::table('classes')
            ->orderBy('nom')
            ->get(['id', 'nom', 'sigle'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'nom' => $row->nom,
                'sigle' => $row->sigle,
            ]);

        $matieres = DB::table('matieres')
            ->where('actif', true)
            ->orderBy('nom')
            ->get(['id', 'nom', 'sigle'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'nom' => $row->nom,
                'sigle' => $row->sigle,
            ]);

        return response()->json([
            'data' => [
                'type_cours' => $typeCours->values(),
                'classes' => $classes->values(),
                'matieres' => $matieres->values(),
            ],
        ]);
    }
}