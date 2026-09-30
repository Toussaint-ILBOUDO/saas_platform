<?php

namespace App\Modules\Public\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CabinetPublicResource;
use App\Models\ParametrePublic;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Informations publiques du cabinet courant (T3.3) : thème, pied de page,
 * fonctionnalités actives — source de personnalisation du frontend.
 */
class CabinetPublicApiController extends Controller
{
    public function index(): JsonResponse
    {
        $public = ParametrePublic::first();

        $resource = new CabinetPublicResource(
            tenant(),
            $public?->theme ?? [],
            $public?->footer ?? [],
            $public?->data ?? []
        );

        return response()->json($resource->resolve(app('request')));
    }

    /**
     * Flux du logo du cabinet (stocké dans parametres_publics.data.logo).
     */
    public function logo(): \Symfony\Component\HttpFoundation\Response
    {
        $public = ParametrePublic::first();
        $chemin = $public?->data['logo']['path'] ?? null;

        if (! $chemin || ! Storage::disk('public')->exists($chemin)) {
            return response()->json([
                'message' => 'Aucun logo configuré.',
                'code' => 'INTROUVABLE',
            ], 404);
        }

        $contenu = Storage::disk('public')->get($chemin);
        $mime = Storage::disk('public')->mimeType($chemin) ?: 'image/png';

        return response($contenu, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=86400',
            'Content-Disposition' => 'inline',
        ]);
    }
}