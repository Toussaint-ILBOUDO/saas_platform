<?php

namespace App\Modules\Librairie\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProduitPublicResource;
use App\Modules\Librairie\Services\LibrairieService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicProduitApiController extends Controller
{
    public function __construct(protected LibrairieService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $produits = $this->service->paginateProduitsPublic([
            'search' => $request->input('search'),
            'categorie_id' => $request->input('categorie_id'),
            'sort' => $request->input('sort', 'recent'),
        ]);

        return response()->json([
            'data' => ProduitPublicResource::collection($produits),
            'meta' => [
                'total' => $produits->total(),
                'per_page' => $produits->perPage(),
                'current_page' => $produits->currentPage(),
                'last_page' => $produits->lastPage(),
            ],
        ]);
    }
}