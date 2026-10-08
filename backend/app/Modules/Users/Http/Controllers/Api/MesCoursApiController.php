<?php

namespace App\Modules\Users\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContratCours;
use App\Modules\Pedagogie\Http\Resources\ContratCoursResource;
use App\Modules\Pedagogie\Services\ContratCoursQueryService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * « Mes cours » (T7A.3) — l'écran enseignant et élève de l'espace personnel.
 *
 * Le filtrage n'est **pas** un paramètre de requête : il est déduit du profil
 * du connecté. Un enseignant ne voit que les contrats auxquels il est affecté,
 * un élève que les siens. Aucune fuite possible par un `?enseignant_id=`.
 */
class MesCoursApiController extends Controller
{
    public function __construct(
        private readonly ContratCoursQueryService $queries
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $perPage = (int) $request->integer('per_page', $request->integer('par_page', 20));

        if ($user->enseignantProfil) {
            return ContratCoursResource::collection(
                $this->queries->paginateForEnseignant($user->enseignantProfil->id, $perPage)
            );
        }

        if ($user->eleve) {
            return ContratCoursResource::collection(
                $this->queries->paginateForEleve($user->eleve->id, $perPage)
            );
        }

        // Un parent n'a pas d'espace « mes cours » côté enseignant/élève : on lui
        // renvoie une liste vide plutôt qu'une 403, et le portail parent expose
        // ses factures par ailleurs (T7A.8).
        return ContratCoursResource::collection(
            ContratCours::query()->whereRaw('1 = 0')->paginate($perPage)
        );
    }
}