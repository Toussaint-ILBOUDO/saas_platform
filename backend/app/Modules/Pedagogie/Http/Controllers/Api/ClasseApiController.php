<?php

namespace App\Modules\Pedagogie\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classe;
use App\Modules\Pedagogie\Http\Requests\StoreClasseRequest;
use App\Modules\Pedagogie\Http\Requests\UpdateClasseRequest;
use App\Modules\Pedagogie\Http\Resources\ClasseResource;
use App\Modules\Pedagogie\Services\ClasseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * API référentiel — classes (T7A.2).
 *
 * Le référentiel est le socle de tout le reste : sans classe stable, ni les
 * affectations, ni les plannings, ni la facturation par ligne ne tiennent.
 * Le web (ClasseController) et l'API passent par le MÊME ClasseService :
 * une règle métier ne doit exister qu'à un seul endroit.
 */
class ClasseApiController extends Controller
{
    public function __construct(
        private readonly ClasseService $service
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $classes = $this->service->paginate(
            filters: $request->filled('search') ? ['search' => $request->string('search')->toString()] : [],
            perPage: (int) $request->integer('per_page', $request->integer('par_page', 15))
        );

        return ClasseResource::collection($classes);
    }

    public function store(StoreClasseRequest $request): JsonResponse
    {
        $classe = $this->service->create($request->validated());

        return ClasseResource::make($classe->loadCount(['eleves', 'demandesCours']))
            ->toResponse(request())
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Classe $classe): JsonResponse
    {
        return ClasseResource::make($classe->loadCount(['eleves', 'demandesCours']))
            ->toResponse(request());
    }

    public function update(UpdateClasseRequest $request, Classe $classe): JsonResponse
    {
        $classe = $this->service->update($classe, $request->validated());

        return ClasseResource::make($classe->loadCount(['eleves', 'demandesCours']))
            ->toResponse(request());
    }

    public function destroy(Classe $classe): JsonResponse
    {
        // Une classe porteuse d'élèves ou de demandes de cours n'est pas
        // supprimable. Contrairement à une matière ou un type de cours, la
        // table `classes` n'a pas de colonne `actif` : on ne promet donc pas
        // une « désactivation » qui n'existe pas côté API, on dit ce qu'il
        // faut réellement faire.
        if ($classe->eleves()->exists() || $classe->demandesCours()->exists()) {
            return response()->json([
                'message' => 'Cette classe comporte des élèves ou des demandes de cours : elle ne peut pas être supprimée. Réaffectez-les d\'abord, ou renommez-la si elle n\'est plus utilisée.',
                'code' => 'CLASSE_UTILISEE',
            ], Response::HTTP_CONFLICT);
        }

        $this->service->delete($classe);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}