<?php

namespace App\Modules\Librairie\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Modules\Librairie\Http\Requests\PasserCommandeRequest;
use App\Modules\Librairie\Services\CommandePdfService;
use App\Modules\Librairie\Services\LibrairieService;
use App\Modules\Systeme\Services\NotificationDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicCommandeApiController extends Controller
{
    public function __construct(
        protected LibrairieService $service,
        protected NotificationDispatcher $dispatcher,
        protected CommandePdfService $pdf,
    ) {
    }

    /**
     * Dépôt d'une commande. Tous les montants sont recalculés côté serveur ;
     * `cle_idempotence` rend l'opération rejouable sans double commande.
     */
    public function store(PasserCommandeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $lignes = $data['panier'];
        unset($data['panier']);

        $commande = $this->service->passCommande($data, $lignes);

        $this->dispatcher->newOrder($commande);

        return response()->json([
            'message' => 'Commande enregistrée.',
            'commande' => [
                'reference' => $commande->reference,
                'date' => $commande->created_at?->toIso8601String(),
                'statut' => $commande->statut,
                'montant_total' => (float) $commande->montant_total,
                'frais_livraison' => (float) $commande->frais_livraison,
                'is_livraison' => (bool) $commande->is_livraison,
                // Lien de suivi côté Angular (aucune route web n'est requise).
                'lien_suivi' => '/suivi-commande?code='.$commande->reference,
                'pdf' => '/api/public/commandes/'.$commande->reference.'/recapitulatif',
            ],
        ], 201);
    }

    /**
     * Suivi public par code de commande : chronologie + informations
     * strictement nécessaires au client (aucune donnée interne).
     */
    public function suivre(string $reference): JsonResponse
    {
        return response()->json([
            'data' => $this->service->suivreCommande($reference),
        ]);
    }

    /**
     * Récapitulatif PDF généré depuis les données ENREGISTRÉES en base
     * (jamais depuis les montants transmis par le navigateur).
     */
    public function recapitulatif(string $reference): Response
    {
        $commande = $this->service->getCommandeParReference($reference);

        return $this->pdf->download($commande);
    }

    /**
     * Annulation par le client : immédiate tant que la commande est en
     * attente, demande tracée une fois confirmée, refusée ensuite.
     */
    public function annuler(Request $request, string $reference): JsonResponse
    {
        $request->validate([
            'motif' => ['nullable', 'string', 'max:500'],
        ]);

        $commande = $this->service->getCommandeParReference($reference);

        $commande = $this->service->demanderAnnulation($commande, $request->input('motif'));

        return response()->json([
            'message' => $commande->estAnnulee()
                ? 'Votre commande a bien été annulée.'
                : 'Votre demande d\'annulation a bien été transmise au personnel de la librairie.',
            'statut' => $commande->statut,
            'annulation_demandee' => $commande->annulation_demandee_at !== null,
        ]);
    }
}
