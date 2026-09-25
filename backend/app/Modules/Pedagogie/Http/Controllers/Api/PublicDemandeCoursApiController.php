<?php

namespace App\Modules\Pedagogie\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Pedagogie\Http\Requests\StoreDemandeCoursRequest;
use App\Modules\Pedagogie\Services\DemandeCoursService;
use Illuminate\Http\JsonResponse;

class PublicDemandeCoursApiController extends Controller
{
    public function __construct(protected DemandeCoursService $service)
    {
    }

    public function store(StoreDemandeCoursRequest $request): JsonResponse
    {
        $demande = $this->service->create($request->validated());

        return response()->json([
            'message' => 'Demande de cours envoyée avec succès.',
            'demande' => [
                'id' => $demande->id,
                'statut' => $demande->statut,
            ],
        ], 201);
    }
}