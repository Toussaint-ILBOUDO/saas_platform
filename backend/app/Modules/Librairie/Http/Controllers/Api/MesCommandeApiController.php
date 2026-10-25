<?php

namespace App\Modules\Librairie\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Modules\Librairie\Services\CommandePdfService;
use App\Modules\Librairie\Services\LibrairieService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Commandes du compte connecté (espace personnel « Mes commandes »).
 *
 * La consultation se fait PAR RÉFÉRENCE publique, jamais par id interne :
 * modifier un identifiant dans l'URL ou dans une requête API ne donne accès
 * qu'à ses propres commandes (403 sinon).
 */
class MesCommandeApiController extends Controller
{
    public function __construct(
        protected LibrairieService $service,
        protected CommandePdfService $pdf,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 15), 50);

        $commandes = Commande::forUser($request->user('web')->id)
            ->withTotalArticles()
            ->with('lignes.produit.media')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'data' => $commandes->getCollection()->map(fn (Commande $commande) => $this->synthese($commande)),
            'meta' => [
                'total' => $commandes->total(),
                'per_page' => $commandes->perPage(),
                'current_page' => $commandes->currentPage(),
                'last_page' => $commandes->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, string $reference): JsonResponse
    {
        $commande = $this->appartientAuCompte($request, $reference);

        return response()->json([
            'data' => [
                ...$this->synthese($commande),
                'lignes' => $commande->lignes->map(fn ($ligne) => [
                    'produit' => $ligne->produit?->nom,
                    'slug' => $ligne->produit?->slug,
                    'image_url' => $ligne->produit?->imageUrl('thumb'),
                    'quantite' => $ligne->quantite,
                    'prix_unitaire' => (float) $ligne->prix_unitaire,
                    'sous_total' => (float) $ligne->sous_total,
                ]),
                'etapes' => $commande->etapesSuivi(),
                'quartier' => $commande->quartier,
                'point_de_repere' => $commande->point_de_repere,
                'lien_geolocalisation' => $commande->lien_geolocalisation,
                'notes' => $commande->notes,
                'adresse_livraison' => $commande->adresse_livraison,
                'telephone_client' => $commande->telephone_client,
                'whatsapp' => $commande->whatsapp,
                'prenom_client' => $commande->prenom_client,
            ],
        ]);
    }

    public function pdf(Request $request, string $reference): Response
    {
        $commande = $this->appartientAuCompte($request, $reference);

        return $this->pdf->download($commande);
    }

    public function annuler(Request $request, string $reference): JsonResponse
    {
        $request->validate([
            'motif' => ['nullable', 'string', 'max:500'],
        ]);

        $commande = $this->appartientAuCompte($request, $reference);
        $commande = $this->service->demanderAnnulation($commande, $request->input('motif'));

        return response()->json([
            'message' => $commande->estAnnulee()
                ? 'Votre commande a bien été annulée.'
                : 'Votre demande d\'annulation a bien été transmise au personnel de la librairie.',
            'statut' => $commande->statut,
            'annulation_demandee' => $commande->annulation_demandee_at !== null,
        ]);
    }

    protected function appartientAuCompte(Request $request, string $reference): Commande
    {
        $commande = $this->service->getCommandeParReference($reference);

        abort_unless(
            $commande->user_id !== null && $commande->user_id === $request->user('web')->id,
            403,
            'Vous ne pouvez consulter que vos propres commandes.'
        );

        return $commande;
    }

    protected function synthese(Commande $commande): array
    {
        return [
            'reference' => $commande->reference,
            'date' => $commande->created_at?->toIso8601String(),
            'statut' => $commande->statut,
            'statut_libelle' => Commande::statutLabel($commande->statut),
            'is_livraison' => (bool) $commande->is_livraison,
            'montant_total' => (float) $commande->montant_total,
            'frais_livraison' => (float) $commande->frais_livraison,
            'total_articles' => (int) ($commande->total_articles ?? $commande->lignes->sum('quantite')),
            'annulable' => $commande->estAnnulableParClient(),
            'annulation_demandee' => $commande->annulation_demandee_at !== null,
        ];
    }
}
