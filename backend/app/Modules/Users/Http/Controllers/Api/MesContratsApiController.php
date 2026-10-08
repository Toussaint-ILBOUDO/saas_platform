<?php

namespace App\Modules\Users\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContratCours;
use App\Modules\Pedagogie\Http\Resources\ContratCoursResource;
use App\Modules\Pedagogie\Services\ContratCoursQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * `GET /api/mes-contrats` — les contrats des enfants du parent connecté, en
 * lecture seule.
 *
 * L'administration possède `/api/pedagogie/contrats` (réservé `role:admin_cabinet`)
 * : le parent qui reçoit la notification « Nouveau contrat de cours » ne peut
 * donc pas l'ouvrir — l'appel répond 403. Cet endpoint est l'exact pendant
 * « famille » de cet écran d'administration, sur le même modèle que
 * `mes-factures` par rapport à `finance/factures`.
 *
 * La fiche est protégée par `ContratCoursPolicy::view`, qui autorise déjà le
 * parent du contrat (et l'enseignant affecté, et l'élève concerné) : le
 * contrôle d'accès vit dans la policy, pas dans le contrôleur.
 */
class MesContratsApiController extends Controller
{
    public function __construct(
        private readonly ContratCoursQueryService $queries
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $parentId = (int) $request->user()?->id;
        $perPage = (int) $request->integer('per_page', $request->integer('par_page', 20));

        return ContratCoursResource::collection(
            $this->queries->paginateForParent($parentId, $perPage)
        );
    }

    public function show(Request $request, ContratCours $contrat): JsonResponse
    {
        $this->authorize('view', $contrat);

        return ContratCoursResource::make($this->queries->show($contrat))->toResponse($request);
    }
}
