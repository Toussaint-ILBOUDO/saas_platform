<?php

namespace App\Modules\Public\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\EnseignantPublicResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

/**
 * Enseignants publics (P2) : mêmes critères que la page d'accueil KEduc
 * (statut actif, profil chargé) mais en JSON pour le frontend.
 */
class PublicEnseignantApiController extends Controller
{
    public function index(): JsonResponse
    {
        $enseignants = Role::where('name', 'enseignant')->exists()
            ? User::query()
                ->role('enseignant')
                ->with(['media', 'enseignantProfil', 'enseignantProfil.matieres'])
                ->where('statut', true)
                ->latest('id')
                ->take(12)
                ->get()
            : collect();

        return response()->json([
            'data' => EnseignantPublicResource::collection($enseignants),
        ]);
    }
}