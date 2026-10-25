<?php

namespace App\Modules\Librairie\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Librairie\Services\LibrairieService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Devis du panier : le serveur recalcule les prix officiels. Le navigateur
 * n'envoie que des identifiants et des quantités.
 */
class PublicPanierApiController extends Controller
{
    public function __construct(protected LibrairieService $service)
    {
    }

    public function devis(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.produit_id' => ['required', 'integer'],
            'lignes.*.quantite' => ['required', 'integer', 'min:1', 'max:999'],
        ], [
            'lignes.required' => 'Votre panier est vide.',
            'lignes.min' => 'Votre panier doit contenir au moins un article.',
        ]);

        return response()->json([
            'data' => $this->service->devis($data['lignes']),
        ]);
    }
}
