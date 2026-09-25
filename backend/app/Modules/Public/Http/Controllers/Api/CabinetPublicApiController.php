<?php

namespace App\Modules\Public\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CabinetPublicResource;
use App\Models\ParametrePublic;
use Illuminate\Http\JsonResponse;

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
}