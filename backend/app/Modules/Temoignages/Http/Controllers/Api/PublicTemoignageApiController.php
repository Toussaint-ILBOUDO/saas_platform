<?php

namespace App\Modules\Temoignages\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TemoignagePublicResource;
use App\Models\Temoignage;
use App\Modules\Temoignages\Services\TemoignageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Témoignages publics (P2) : classement R1/B1/R2… via TemoignageService,
 * cache 3600 s — même source que la page « Témoignages ».
 */
class PublicTemoignageApiController extends Controller
{
    public function __construct(protected TemoignageService $service)
    {
    }

    /**
     * Liste paginée (le « Voir plus » de la page publique en a besoin).
     * `per_page` : 6 par défaut, 50 au maximum.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 6), 1), 50);
        $temoignages = $this->service->listeClassement($perPage);

        return TemoignagePublicResource::collection($temoignages)->response();
    }

    public function show(string $slug): JsonResponse
    {
        $temoignage = Temoignage::query()
            ->publie()
            ->withScore()
            ->with('auteur:id,prenom,nom')
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json(new TemoignagePublicResource($temoignage));
    }
}
