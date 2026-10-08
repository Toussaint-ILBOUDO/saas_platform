<?php

namespace App\Modules\Pedagogie\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AffectationEnseignant;
use App\Models\ContratCours;
use App\Modules\Pedagogie\Http\Requests\StoreAffectationRequest;
use App\Modules\Pedagogie\Http\Requests\UpdateAffectationRequest;
use App\Modules\Pedagogie\Http\Resources\AffectationEnseignantResource;
use App\Modules\Pedagogie\Services\AffectationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Affectations enseignant d'un contrat (T7A.3).
 *
 * Pas de `destroy` — voir `ContratCoursApiController` : la suppression en
 * cascade effacerait les heures facturées et payées. Une affectation se
 * suspend ou se termine.
 */
class AffectationApiController extends Controller
{
    public function __construct(
        private readonly AffectationService $service
    ) {}

    public function store(StoreAffectationRequest $request, ContratCours $contrat): JsonResponse
    {
        $affectation = $this->service->create($contrat, $request->validated());

        return AffectationEnseignantResource::make($affectation)
            ->toResponse(request())
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        UpdateAffectationRequest $request,
        ContratCours $contrat,
        AffectationEnseignant $affectation
    ): JsonResponse {
        // Le contrat de l'URL doit être celui de l'affectation : sans ce contrôle
        // on pourrait réécrire une affectation d'un autre contrat.
        abort_unless((int) $affectation->contrat_cours_id === (int) $contrat->id, 404);

        $affectation = $this->service->update($affectation, $request->validated());

        return AffectationEnseignantResource::make($affectation)->toResponse(request());
    }

    public function statut(
        Request $request,
        ContratCours $contrat,
        AffectationEnseignant $affectation
    ): JsonResponse {
        abort_unless((int) $affectation->contrat_cours_id === (int) $contrat->id, 404);

        $affectation = $this->service->changerStatut(
            $affectation,
            (string) $request->input('statut')
        );

        return AffectationEnseignantResource::make($affectation)->toResponse(request());
    }
}