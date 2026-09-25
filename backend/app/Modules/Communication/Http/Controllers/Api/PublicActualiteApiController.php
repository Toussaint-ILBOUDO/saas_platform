<?php

namespace App\Modules\Communication\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ActualitePublicResource;
use App\Models\Actualite;
use App\Modules\Communication\Services\ActualiteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicActualiteApiController extends Controller
{
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
}