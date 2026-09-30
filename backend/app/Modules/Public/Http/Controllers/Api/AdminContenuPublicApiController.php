<?php

namespace App\Modules\Public\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParametrePublic;
use App\Modules\Public\Http\Requests\Api\StoreLogoCabinetRequest;
use App\Modules\Public\Http\Requests\Api\UpdateContenuPublicApiRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

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
            // Fusion récursive : préserve « data.logo » et autres clés déjà en
            // place (la requête ne fournit que « data.fiche »).
            'data' => array_replace_recursive($public?->data ?? [], $validated['data'] ?? []),
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

    public function mettreAJourLogo(StoreLogoCabinetRequest $request): JsonResponse
    {
        $public = ParametrePublic::query()->firstOrNew();
        $disque = Storage::disk('public');

        $ancien = $public->data['logo']['path'] ?? null;
        if ($ancien && $disque->exists($ancien)) {
            $disque->delete($ancien);
        }

        $chemin = $request->file('logo')->store('logos', 'public');

        $donnees = $public->data ?? [];
        $donnees['logo'] = [
            'path' => $chemin,
            'updated_at' => now()->timestamp,
        ];
        $public->data = $donnees;
        $public->save();

        return response()->json([
            'message' => 'Logo mis à jour.',
            'data' => [
                'theme' => $public->theme ?? [],
                'footer' => $public->footer ?? [],
                'data' => $public->data ?? [],
            ],
        ]);
    }
}