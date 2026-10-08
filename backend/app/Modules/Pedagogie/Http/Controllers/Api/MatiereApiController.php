<?php

namespace App\Modules\Pedagogie\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Matiere;
use App\Modules\Pedagogie\Http\Requests\StoreMatiereRequest;
use App\Modules\Pedagogie\Http\Requests\UpdateMatiereRequest;
use App\Modules\Pedagogie\Http\Resources\MatiereResource;
use App\Modules\Pedagogie\Services\MatiereService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * API référentiel — matières (T7A.2).
 *
 * Une matière porteuse d Affectations, d'objectifs ou de demandes de cours ne
 * doit pas être supprimée : la désactivation (`actif = false`) est le chemin
 * prévu, elle la retire des listes de saisie sans casser l'historique.
 */
class MatiereApiController extends Controller
{
    public function __construct(
        private readonly MatiereService $service
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = [];

        if ($request->filled('search')) {
            $filters['search'] = $request->string('search')->toString();
        }

        if ($request->has('actif') && $request->query('actif') !== '') {
            $filters['actif'] = $request->boolean('actif');
        }

        $matieres = $this->service->paginate($filters, (int) $request->integer('per_page', $request->integer('par_page', 15)));

        return MatiereResource::collection($matieres);
    }

    public function store(StoreMatiereRequest $request): JsonResponse
    {
        $matiere = $this->service->create($request->validated());

        return MatiereResource::make($matiere)
            ->toResponse(request())
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Matiere $matiere): JsonResponse
    {
        return MatiereResource::make($matiere)->toResponse(request());
    }

    public function update(UpdateMatiereRequest $request, Matiere $matiere): JsonResponse
    {
        $matiere = $this->service->update($matiere, $request->validated());

        return MatiereResource::make($matiere)->toResponse(request());
    }

    public function activer(Matiere $matiere): JsonResponse
    {
        $this->service->activate($matiere);

        return MatiereResource::make($matiere->fresh())->toResponse(request());
    }

    public function desactiver(Matiere $matiere): JsonResponse
    {
        $this->service->deactivate($matiere);

        return MatiereResource::make($matiere->fresh())->toResponse(request());
    }

    public function destroy(Matiere $matiere): JsonResponse
    {
        if ($matiere->affectations()->exists() || $matiere->objectifs()->exists()) {
            return response()->json([
                'message' => 'Cette matière est utilisée : elle porte des affectations ou des objectifs pédagogiques. Désactivez-la plutôt.',
                'code' => 'MATIERE_UTILISEE',
            ], Response::HTTP_CONFLICT);
        }

        $this->service->delete($matiere);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}