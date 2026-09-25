<?php

namespace App\Modules\Public\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParametrePublic;
use App\Modules\Public\Http\Requests\Api\UpdateContenuPublicApiRequest;
use Illuminate\Http\JsonResponse;

/**
 * Contenu public du site (thème, pied de page, données) — backoffice MVP T3.5.
 * Une seule ligne dans « parametres_publics » (D-014/D-039).
 */
class AdminContenuPublicApiController extends Controller
{
    public function show(): JsonResponse
    {
        $public = ParametrePublic::query()->firstOrNew();

        return response()->json([
            'data' => [
                'theme' => $public->theme ?? [],
                'footer' => $public->footer ?? [],
                'data' => $public->data ?? [],
            ],
        ]);
    }

    public function update(UpdateContenuPublicApiRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $public = ParametrePublic::query()->first();
        $data = [
            'theme' => $validated['theme'] ?? $public?->theme ?? [],
            'footer' => $validated['footer'] ?? $public?->footer ?? [],
            'data' => $validated['data'] ?? $public?->data ?? [],
        ];

        if ($public) {
            $public->update($data);
        } else {
            $public = ParametrePublic::create($data);
        }

        return response()->json([
            'message' => 'Contenu public mis à jour.',
            'data' => [
                'theme' => $public->theme ?? [],
                'footer' => $public->footer ?? [],
                'data' => $public->data ?? [],
            ],
        ]);
    }
}