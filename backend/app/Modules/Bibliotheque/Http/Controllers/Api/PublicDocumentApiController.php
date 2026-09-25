<?php

namespace App\Modules\Bibliotheque\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\DocumentPublicResource;
use App\Modules\Bibliotheque\Services\DocumentBibliothequeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicDocumentApiController extends Controller
{
    public function __construct(protected DocumentBibliothequeService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 12), 50);

        $documents = $this->service->paginatePublic([
            'search' => $request->input('search'),
            'type_document_id' => $request->input('type_document_id'),
            'classe_id' => $request->input('classe_id'),
            'matiere_id' => $request->input('matiere_id'),
            'periode_id' => $request->input('periode_id'),
            'tag' => $request->input('tag'),
            'sort' => $request->input('sort'),
        ], $perPage);

        return response()->json([
            'data' => DocumentPublicResource::collection($documents),
            'meta' => [
                'total' => $documents->total(),
                'per_page' => $documents->perPage(),
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $document = $this->service->getBySlug($slug);

        abort_unless($document->is_public && $document->statut === 'publie', 404);

        return response()->json(new DocumentPublicResource($document));
    }
}