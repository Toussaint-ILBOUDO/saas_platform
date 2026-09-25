<?php

namespace App\Modules\Communication\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ActualiteAdminResource;
use App\Models\Actualite;
use App\Modules\Communication\Http\Requests\StoreActualiteRequest;
use App\Modules\Communication\Http\Requests\UpdateActualiteRequest;
use App\Modules\Communication\Services\ActualiteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Backoffice actualités — backoffice MVP T3.5. Réutilise le service et les
 * FormRequests KEduc existants (D-039) : seuls contrôleur API et Resource
 * sont ajoutés.
 */
class AdminActualiteApiController extends Controller
{
    public function __construct(protected ActualiteService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 15), 50);
        $actualites = $this->service->listAdmin(
            (string) $request->input('statut', ''),
            (string) $request->input('search', ''),
            $perPage
        );

        return response()->json([
            'data' => ActualiteAdminResource::collection($actualites),
            'meta' => [
                'total' => $actualites->total(),
                'per_page' => $actualites->perPage(),
                'current_page' => $actualites->currentPage(),
                'last_page' => $actualites->lastPage(),
            ],
        ]);
    }

    public function store(StoreActualiteRequest $request): JsonResponse
    {
        $this->authorize('create', Actualite::class);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $actualite = $this->service->create(
            $data,
            $request->user()->id,
            $request->file('image_principale'),
            $request->file('galerie') ?? [],
            $request->file('document')
        );

        return response()->json([
            'message' => 'Actualité créée.',
            'data' => new ActualiteAdminResource($actualite->fresh()),
        ], 201);
    }

    public function show(Actualite $actualite): JsonResponse
    {
        $this->authorize('view', $actualite);

        return response()->json(['data' => new ActualiteAdminResource($actualite)]);
    }

    public function update(UpdateActualiteRequest $request, Actualite $actualite): JsonResponse
    {
        $this->authorize('update', $actualite);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', $actualite->is_active);

        $actualite = $this->service->update(
            $actualite,
            $data,
            $request->file('image_principale'),
            $request->file('galerie') ?? [],
            $request->file('document')
        );

        return response()->json([
            'message' => 'Actualité mise à jour.',
            'data' => new ActualiteAdminResource($actualite),
        ]);
    }

    public function destroy(Actualite $actualite): JsonResponse
    {
        $this->authorize('delete', $actualite);

        $this->service->delete($actualite);

        return response()->json(['message' => 'Actualité supprimée.']);
    }
}