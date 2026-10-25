<?php

namespace App\Modules\Librairie\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProduitPublicResource;
use App\Modules\Librairie\Services\LibrairieService;
use App\Support\SertMediaPublic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicProduitApiController extends Controller
{
    use SertMediaPublic;

    public function __construct(protected LibrairieService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $produits = $this->service->paginateProduitsPublic([
            'search' => $request->input('search'),
            'categorie_id' => $request->input('categorie_id'),
            'sort' => $request->input('sort', 'recent'),
            'prix_min' => $request->filled('prix_min') ? (float) $request->input('prix_min') : null,
            'prix_max' => $request->filled('prix_max') ? (float) $request->input('prix_max') : null,
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

    /**
     * Catégories visibles sur la page publique, avec nombre de produits
     * réellement publiés (jamais d'archivé, jamais de dépublié).
     */
    public function categories(): JsonResponse
    {
        return response()->json([
            'data' => $this->service->getCategoriesWithProduitsCount()->map(fn ($categorie) => [
                'id' => $categorie->id,
                'nom' => $categorie->nom,
                'slug' => $categorie->slug,
                'description' => $categorie->description,
                'produits' => (int) ($categorie->produits_actifs_count ?? 0),
            ]),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $produit = $this->service->getProduitBySlug($slug);

        $similaires = $this->service->getProduitsSimilaires($produit, 4);

        return response()->json([
            'data' => new ProduitPublicResource($produit),
            'similaires' => ProduitPublicResource::collection($similaires),
        ]);
    }

    /**
     * Image du produit en ligne (catalogue public). Conversion optionnelle
     * (`thumb` / `small`) ; fallback sur l'original si absente.
     * Sans filtre `active()` : une image de produit archivé (historique de
     * commande) reste lisible.
     */
    public function image(string $slug, Request $request): Response
    {
        $produit = \App\Models\Produit::with('media')
            ->where('slug', $slug)
            ->firstOrFail();
        $conversion = $request->query('conversion');

        return $this->repondreMedia(
            $produit->getFirstMedia('image_principale'),
            is_string($conversion) && $conversion !== '' ? $conversion : null
        );
    }
}
