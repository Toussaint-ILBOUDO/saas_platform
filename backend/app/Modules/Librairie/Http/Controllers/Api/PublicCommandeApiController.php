<?php

namespace App\Modules\Librairie\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Librairie\Http\Requests\PasserCommandeRequest;
use App\Modules\Librairie\Services\LibrairieService;
use App\Modules\Systeme\Services\NotificationDispatcher;
use Illuminate\Http\JsonResponse;

class PublicCommandeApiController extends Controller
{
    public function __construct(
        protected LibrairieService $service,
        protected NotificationDispatcher $dispatcher
    ) {
    }

    public function store(PasserCommandeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $lignes = $data['panier'];
        unset($data['panier']);

        $commande = $this->service->passCommande($data, $lignes);

        $this->dispatcher->newOrder($commande);

        return response()->json([
            'message' => 'Commande passée avec succès.',
            'commande' => [
                'id' => $commande->id,
                'token' => $commande->token,
                'montant_total' => $commande->montant_total,
                'frais_livraison' => $commande->frais_livraison,
                'statut' => $commande->statut,
                'lien_suivi' => route('librairie.confirmation', [
                    'commande' => $commande,
                    'token' => $commande->token,
                ]),
            ],
        ], 201);
    }
}