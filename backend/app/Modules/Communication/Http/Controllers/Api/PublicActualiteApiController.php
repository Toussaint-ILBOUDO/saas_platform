<?php

namespace App\Modules\Communication\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ActualitePublicResource;
use App\Models\Actualite;
use App\Modules\Communication\Services\ActualiteService;
use App\Support\SertMediaPublic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicActualiteApiController extends Controller
{
    use SertMediaPublic;

    public function __construct(protected ActualiteService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 9), 50);
        $actualites = $this->service->listPublic($perPage);

        return response()->json([
            'data' => ActualitePublicResource::collection($actualites),
            'meta' => [
                'total' => $actualites->total(),
                'per_page' => $actualites->perPage(),
                'current_page' => $actualites->currentPage(),
                'last_page' => $actualites->lastPage(),
            ],
        ]);
    }

    public function show(string $slug, Request $request): JsonResponse
    {
        $actualite = $this->service->getBySlug($slug);

        $this->service->recordView($actualite, $request);

        $resource = new ActualitePublicResource($actualite);
        $resource->showContenu = true;

        return response()->json($resource->resolve(app('request')));
    }

    /**
     * Image de l'actualité (route contrôlée : le disque public tenant
     * n'est pas exposé via `/storage`).
     */
    public function image(string $slug): Response
    {
        $actualite = $this->service->getBySlug($slug);

        return $this->repondreMedia($actualite->getFirstMedia('image_principale'));
    }
}