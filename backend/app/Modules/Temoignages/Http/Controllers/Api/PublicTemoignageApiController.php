<?php

namespace App\Modules\Temoignages\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TemoignagePublicResource;
use App\Models\Temoignage;
use App\Modules\Temoignages\Services\TemoignageService;
use Illuminate\Http\JsonResponse;

/**
 * Témoignages publics (P2) : classement R1/B1/R2… via TemoignageService,
 * cache 3600 s — même source que la future page « Témoignages ».
 */
class PublicTemoignageApiController extends Controller
{
    public function __construct(protected TemoignageService $service)
    {
    }

    public function index(): JsonResponse
    {
        $temoignages = $this->service->topHome(12);

        return response()->json([
            'data' => TemoignagePublicResource::collection($temoignages),
        ]);
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