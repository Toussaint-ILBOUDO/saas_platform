<?php

namespace App\Modules\Temoignages\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TemoignagePublicResource;
use App\Modules\Temoignages\Http\Requests\StoreTemoignageRequest;
use App\Modules\Temoignages\Services\TemoignageService;
use Illuminate\Http\JsonResponse;

/**
 * Soumission d'un témoignage par un membre connecté (Angular) : publication
 * immédiate, modération a posteriori (masquer/restaurer côté admin).
 * L'anonymat est porté par le champ `anonyme` (le public voit alors
 * « Témoignage anonyme »).
 */
class MesTemoignageApiController extends Controller
{
    public function __construct(protected TemoignageService $service)
    {
    }

    public function store(StoreTemoignageRequest $request): JsonResponse
    {
        $temoignage = $this->service->create($request->validated(), $request->user('web'));

        return response()->json([
            'message' => 'Merci ! Votre témoignage est publié.',
            'temoignage' => new TemoignagePublicResource($temoignage->load('auteur:id,prenom,nom')),
        ], 201);
    }
}
