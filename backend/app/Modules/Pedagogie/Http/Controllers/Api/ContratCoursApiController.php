<?php

namespace App\Modules\Pedagogie\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContratCours;
use App\Modules\Pedagogie\Http\Requests\StoreContratCoursRequest;
use App\Modules\Pedagogie\Http\Requests\UpdateContratCoursRequest;
use App\Modules\Pedagogie\Http\Resources\ContratCoursResource;
use App\Modules\Pedagogie\Services\ContratCoursQueryService;
use App\Modules\Pedagogie\Services\ContratCoursService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * API contrats de cours (T7A.3).
 *
 * Pas de `destroy` : le schéma fait `ON DELETE CASCADE` de `contrat_cours`
 * vers `affectation_enseignants`, puis vers `cahier_textes`, `ligne_factures`
 * et `bulletin_paie_lignes`. Supprimer un contrat effacerait donc silencieusement
 * des heures déjà facturées et payées. Un contrat se suspend ou se termine.
 */
class ContratCoursApiController extends Controller
{
    public function __construct(
        private readonly ContratCoursService $service,
        private readonly ContratCoursQueryService $queries
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = [];

        if ($request->filled('search')) {
            $filters['search'] = $request->string('search')->toString();
        }

        if ($request->has('statut') && $request->query('statut') !== '') {
            $filters['statut'] = $request->string('statut')->toString();
        }

        if ($request->filled('type_cours_id')) {
            $filters['type_cours_id'] = (int) $request->integer('type_cours_id');
        }

        $contrats = $this->queries->paginate($filters, (int) $request->integer('per_page', $request->integer('par_page', 20)));

        return ContratCoursResource::collection($contrats);
    }

    public function store(StoreContratCoursRequest $request): JsonResponse
    {
        $contrat = $this->service->create($request->validated());

        return ContratCoursResource::make($contrat)
            ->toResponse(request())
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(ContratCours $contrat): JsonResponse
    {
        return ContratCoursResource::make($this->queries->show($contrat))->toResponse(request());
    }

    public function update(UpdateContratCoursRequest $request, ContratCours $contrat): JsonResponse
    {
        $contrat = $this->service->update($contrat, $request->validated());

        return ContratCoursResource::make($this->queries->show($contrat))->toResponse(request());
    }

    /**
     * Fait passer le contrat en `suspendu` ou `termine`.
     */
    public function statut(Request $request, ContratCours $contrat): JsonResponse
    {
        $statut = (string) $request->input('statut');

        $contrat = $this->service->changerStatut($contrat, $statut);

        return ContratCoursResource::make($this->queries->show($contrat))->toResponse(request());
    }
}