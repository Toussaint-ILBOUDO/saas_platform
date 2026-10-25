<?php

namespace App\Modules\Librairie\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CategorieProduit;
use App\Models\Commande;
use App\Models\ParametreLibrairie;
use App\Models\Produit;
use App\Support\Roles;
use App\Support\SertMediaPublic;
use App\Modules\Librairie\Http\Requests\StoreCategorieProduitRequest;
use App\Modules\Librairie\Http\Requests\StoreProduitRequest;
use App\Modules\Librairie\Http\Requests\UpdateProduitRequest;
use App\Modules\Librairie\Services\CommandePdfService;
use App\Modules\Librairie\Services\LibrairieService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Console du libraire (gestionnaire_librairie) et de l'administrateur
 * du cabinet.
 *
 * DÉLÉGATION COMMERCIALE : le cabinet peut confier l'exploitation de la
 * librairie à une structure tierce. Le libraire peut donc interdire à
 * l'administrateur général l'accès au tableau de bord, au chiffre
 * d'affaires ou aux commandes clients via les clés de
 * `parametres_librairie`. Ces restrictions sont appliquées ICI, côté
 * serveur : une URL directe, un appel API ou une requête frontend modifiée
 * ne les contourne pas. Le libraire seul peut modifier ces clés — l'admin
 * ne peut pas se réattribuer un accès qu'on lui a retiré.
 *
 * Cette délégation porte sur les DONNÉES COMMERCIALES uniquement : la
 * maintenance technique, la sécurité et la conformité restent du ressort
 * de l'administrateur de la plateforme, hors de ce périmètre.
 */
class AdminLibrairieApiController extends Controller
{
    use SertMediaPublic;

    public function __construct(
        protected LibrairieService $service,
        protected CommandePdfService $pdf,
    ) {
    }

    // ======================
    // PARAMÈTRES & DÉLÉGATION
    // ======================

    public function parametres(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'sigle' => ParametreLibrairie::lire(ParametreLibrairie::CLE_SIGLE),
                'frais_livraison' => ParametreLibrairie::fraisLivraison(),
                'admin_peut_voir_dashboard' => ParametreLibrairie::bool(ParametreLibrairie::CLE_ADMIN_DASHBOARD),
                'admin_peut_voir_ca' => ParametreLibrairie::bool(ParametreLibrairie::CLE_ADMIN_CA),
                'admin_peut_voir_commandes' => ParametreLibrairie::bool(ParametreLibrairie::CLE_ADMIN_COMMANDES),
            ],
        ]);
    }

    /**
     * Réservé au libraire (`role:gestionnaire_librairie` sur la route) :
     * l'administrateur général ne peut ni s'auto-autoriser ni retirer
     * l'accès au libraire.
     */
    public function majParametres(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sigle' => ['nullable', 'string', 'max:8', 'regex:/^[A-Za-z0-9]+$/'],
            'frais_livraison' => ['nullable', 'numeric', 'min:0'],
            'admin_peut_voir_dashboard' => ['nullable', 'boolean'],
            'admin_peut_voir_ca' => ['nullable', 'boolean'],
            'admin_peut_voir_commandes' => ['nullable', 'boolean'],
        ], [
            'sigle.regex' => 'Le sigle ne peut contenir que des lettres et des chiffres.',
            'sigle.max' => 'Le sigle ne peut dépasser 8 caractères.',
            'frais_livraison.min' => 'Les frais de livraison ne peuvent être négatifs.',
        ]);

        if (array_key_exists('sigle', $data)) {
            ParametreLibrairie::ecrire(ParametreLibrairie::CLE_SIGLE, strtoupper($data['sigle']) ?: null);
        }
        if (array_key_exists('frais_livraison', $data)) {
            ParametreLibrairie::ecrire(ParametreLibrairie::CLE_FRAIS_LIVRAISON, (string) max(0, (float) $data['frais_livraison']));
        }

        foreach (ParametreLibrairie::CLES_BOOLEANENNES as $cle) {
            if (array_key_exists($cle, $data)) {
                ParametreLibrairie::ecrire($cle, $data[$cle] ? '1' : '0');
            }
        }

        return $this->parametres($request);
    }

    // ======================
    // DASHBOARD & STATS
    // ======================

    public function dashboard(): JsonResponse
    {
        $this->refuserSiAccesCommercialInterdit(ParametreLibrairie::CLE_ADMIN_DASHBOARD);

        $stats = $this->service->getDashboardStats();
        $caAccessible = $this->peutVoir(ParametreLibrairie::CLE_ADMIN_CA);

        return response()->json([
            'data' => [
                'produits' => $stats['produits'],
                'produits_publies' => $stats['produitsPublies'],
                'produits_archives' => $stats['produitsArchives'],
                'produits_stock_faible' => $stats['produitsStockFaible'],
                'categories' => $stats['categories'],
                'commandes' => $stats['commandes'],
                'commandes_du_jour' => $stats['commandesDuJour'],
                'commandes_par_statut' => collect($stats['commandesParStatut'])
                    ->map(fn ($total, $statut) => ['statut' => $statut, 'total' => (int) $total])
                    ->values(),
                // Chiffre d'affaires = somme des commandes NON annulées
                // (règle affichée dans l'interface). Masqué si le libraire
                // a retiré cet accès à l'administrateur.
                'ca_accessible' => $caAccessible,
                'chiffre_affaires' => $caAccessible ? $stats['montantTotalVentes'] : null,
                'frais_livraison_encaisses' => $caAccessible ? $stats['montantLivraisons'] : null,
                'dernieres_commandes' => collect($stats['dernieresCommandes'])->map(fn (Commande $commande) => [
                    'reference' => $commande->reference,
                    'nom_client' => $commande->nom_client,
                    'statut' => $commande->statut,
                    'montant_total' => (float) $commande->montant_total,
                    'is_livraison' => (bool) $commande->is_livraison,
                    'total_articles' => (int) ($commande->total_articles ?? 0),
                    'date' => $commande->created_at?->toIso8601String(),
                ]),
                'top_produits' => collect($stats['topProduits'])->map(fn (Produit $produit) => [
                    'id' => $produit->id,
                    'nom' => $produit->nom,
                    'image_url' => $this->urlImageAdmin($produit, 'thumb'),
                ]),
            ],
        ]);
    }

    // ======================
    // CATÉGORIES
    // ======================

    public function categories(): JsonResponse
    {
        $this->refuserSiAccesCommercialInterdit(ParametreLibrairie::CLE_ADMIN_DASHBOARD);

        return response()->json([
            'data' => $this->service->getCategoriesActives()->map(fn (CategorieProduit $categorie) => [
                'id' => $categorie->id,
                'nom' => $categorie->nom,
                'slug' => $categorie->slug,
                'ordre' => $categorie->ordre,
            ]),
        ]);
    }

    public function storeCategorie(StoreCategorieProduitRequest $request): JsonResponse
    {
        $this->refuserSiAccesCommercialInterdit(ParametreLibrairie::CLE_ADMIN_DASHBOARD);

        $categorie = $this->service->createCategorie($request->validated());

        return response()->json([
            'message' => 'Catégorie créée.',
            'categorie' => [
                'id' => $categorie->id,
                'nom' => $categorie->nom,
                'slug' => $categorie->slug,
            ],
        ], 201);
    }

    // ======================
    // PRODUITS
    // ======================

    public function produits(Request $request): JsonResponse
    {
        $this->refuserSiAccesCommercialInterdit(ParametreLibrairie::CLE_ADMIN_DASHBOARD);

        $produits = $this->service->paginateAdminProduits([
            'search' => $request->input('search'),
            'categorie_id' => $request->input('categorie_id'),
            'is_active' => $request->input('is_active'),
        ]);

        return response()->json([
            'data' => $produits->getCollection()->map(fn (Produit $produit) => [
                'id' => $produit->id,
                'slug' => $produit->slug,
                'reference' => $produit->reference,
                'nom' => $produit->nom,
                'description' => $produit->description,
                'prix' => (float) $produit->prix,
                'prix_promo' => $produit->prix_promo !== null ? (float) $produit->prix_promo : null,
                'stock' => $produit->stock,
                'categorie' => $produit->categorie?->nom,
                'categorie_id' => $produit->categorie_id,
                'image_url' => $this->urlImageAdmin($produit, 'thumb'),
                'is_active' => (bool) $produit->is_active,
                'est_archive' => (bool) $produit->est_archive,
                'vendus' => (int) $produit->lignesCommande()->sum('quantite'),
                'date' => $produit->created_at?->toIso8601String(),
            ]),
            'meta' => [
                'total' => $produits->total(),
                'per_page' => $produits->perPage(),
                'current_page' => $produits->currentPage(),
                'last_page' => $produits->lastPage(),
            ],
        ]);
    }

    public function storeProduit(StoreProduitRequest $request): JsonResponse
    {
        $this->refuserSiAccesCommercialInterdit(ParametreLibrairie::CLE_ADMIN_DASHBOARD);

        $produit = $this->service->createProduit(
            $request->safe()->except(['image']),
            $request->file('image')
        );

        return response()->json([
            'message' => 'Produit créé.',
            'produit' => ['id' => $produit->id, 'slug' => $produit->slug],
        ], 201);
    }

    public function updateProduit(UpdateProduitRequest $request, Produit $produit): JsonResponse
    {
        $this->refuserSiAccesCommercialInterdit(ParametreLibrairie::CLE_ADMIN_DASHBOARD);

        $this->service->updateProduit(
            $produit,
            $request->safe()->except(['image']),
            $request->file('image')
        );

        return response()->json(['message' => 'Produit mis à jour.']);
    }

    /**
     * Suppression douce : un produit déjà commandé est ARCHIVÉ pour
     * conserver l'historique commercial.
     */
    public function destroyProduit(Produit $produit): JsonResponse
    {
        $this->refuserSiAccesCommercialInterdit(ParametreLibrairie::CLE_ADMIN_DASHBOARD);

        $this->service->deleteProduit($produit);

        return response()->json([
            'message' => $produit->lignesCommande()->exists()
                ? 'Produit archivé (il apparaissait dans des commandes : l\'historique est conservé).'
                : 'Produit supprimé.',
        ]);
    }

    public function basculerProduit(Produit $produit): JsonResponse
    {
        $this->refuserSiAccesCommercialInterdit(ParametreLibrairie::CLE_ADMIN_DASHBOARD);

        $produit->update(['is_active' => ! $produit->is_active]);

        return response()->json([
            'message' => $produit->is_active ? 'Produit publié.' : 'Produit dépublié.',
            'is_active' => (bool) $produit->is_active,
        ]);
    }

    /**
     * Image du produit dans la console libraire (route contrôlée : le disque
     * public tenant n'est pas exposé via `/storage`).
     */
    public function imageProduit(Produit $produit, Request $request): Response
    {
        $this->refuserSiAccesCommercialInterdit(ParametreLibrairie::CLE_ADMIN_DASHBOARD);

        $conversion = $request->query('conversion');

        return $this->repondreMedia(
            $produit->getFirstMedia('image_principale'),
            is_string($conversion) && $conversion !== '' ? $conversion : null
        );
    }

    // ======================
    // COMMANDES
    // ======================

    public function commandes(Request $request): JsonResponse
    {
        $this->refuserSiAccesCommercialInterdit(ParametreLibrairie::CLE_ADMIN_COMMANDES);

        $commandes = $this->service->paginateCommandes([
            'search' => $request->input('search'),
            'statut' => $request->input('statut'),
        ]);

        return response()->json([
            'data' => $commandes->getCollection()->map(fn (Commande $commande) => $this->syntheseCommande($commande)),
            'meta' => [
                'total' => $commandes->total(),
                'per_page' => $commandes->perPage(),
                'current_page' => $commandes->currentPage(),
                'last_page' => $commandes->lastPage(),
            ],
        ]);
    }

    public function commande(string $reference): JsonResponse
    {
        $this->refuserSiAccesCommercialInterdit(ParametreLibrairie::CLE_ADMIN_COMMANDES);

        $commande = $this->service->getCommandeParReference($reference);

        return response()->json([
            'data' => [
                ...$this->syntheseCommande($commande),
                'prenom_client' => $commande->prenom_client,
                'telephone_client' => $commande->telephone_client,
                'whatsapp' => $commande->whatsapp,
                'email_client' => $commande->user?->email,
                'adresse_livraison' => $commande->adresse_livraison,
                'quartier' => $commande->quartier,
                'point_de_repere' => $commande->point_de_repere,
                'lien_geolocalisation' => $commande->lien_geolocalisation,
                'notes_client' => $commande->notes,
                'note_interne' => $commande->note_interne,
                'annulation_motif' => $commande->annulation_motif,
                'lignes' => $commande->lignes->map(fn ($ligne) => [
                    'produit' => $ligne->produit?->nom,
                    'reference' => $ligne->produit?->reference,
                    'quantite' => $ligne->quantite,
                    'prix_unitaire' => (float) $ligne->prix_unitaire,
                    'sous_total' => (float) $ligne->sous_total,
                ]),
                'historique' => collect($commande->statut_modifications ?? [])->map(fn ($m) => [
                    'ancien_statut' => $m['ancien_statut'] ?? null,
                    'nouveau_statut' => $m['nouveau_statut'] ?? null,
                    'date' => $m['created_at'] ?? null,
                ]),
                'pdf' => '/api/admin/librairie/commandes/'.$commande->reference.'/pdf',
            ],
        ]);
    }

    public function majStatut(Request $request, string $reference): JsonResponse
    {
        $this->refuserSiAccesCommercialInterdit(ParametreLibrairie::CLE_ADMIN_COMMANDES);

        $data = $request->validate([
            'statut' => ['required', 'string', 'in:'.implode(',', Commande::STATUTS)],
            'motif' => ['nullable', 'string', 'max:500'],
        ], [
            'statut.required' => 'Le statut est obligatoire.',
            'statut.in' => 'Ce statut n\'est pas valide.',
        ]);

        $commande = $this->service->getCommandeParReference($reference);

        try {
            if ($data['statut'] === Commande::STATUT_ANNULEE) {
                $commande = $this->service->annulerCommande($commande, $request->user('web')->id);

                if (! empty($data['motif'])) {
                    $commande->update(['annulation_motif' => $data['motif']]);
                }
            } else {
                $commande = $this->service->changerStatut($commande, $data['statut'], $request->user('web')->id);
            }
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'TRANSITION_INVALIDE',
            ], 422);
        }

        return response()->json([
            'message' => 'Statut mis à jour : '.Commande::statutLabel($commande->statut).'.',
            'statut' => $commande->statut,
        ]);
    }

    public function majNoteInterne(Request $request, string $reference): JsonResponse
    {
        $this->refuserSiAccesCommercialInterdit(ParametreLibrairie::CLE_ADMIN_COMMANDES);

        $data = $request->validate([
            'note_interne' => ['nullable', 'string', 'max:4000'],
        ]);

        $commande = $this->service->getCommandeParReference($reference);
        $commande->update(['note_interne' => $data['note_interne'] ?? null]);

        return response()->json(['message' => 'Note interne enregistrée.']);
    }

    public function pdf(string $reference): Response
    {
        $this->refuserSiAccesCommercialInterdit(ParametreLibrairie::CLE_ADMIN_COMMANDES);

        return $this->pdf->download($this->service->getCommandeParReference($reference));
    }

    // ======================
    // AIDES
    // ======================

    protected function peutVoir(string $cle): bool
    {
        $user = request()->user('web');

        // Le libraire est toujours autorisé sur son périmètre.
        if ($user->hasRole('gestionnaire_librairie')) {
            return true;
        }

        // L'administrateur du cabinet, sous réserve de la délégation.
        if (Roles::estAdmin($user)) {
            return ParametreLibrairie::bool($cle);
        }

        return false;
    }

    protected function refuserSiAccesCommercialInterdit(string $cle): void
    {
        if (! $this->peutVoir($cle)) {
            abort(403, 'L\'accès à ces données commerciales vous a été retiré par le gestionnaire de la librairie.');
        }
    }

    protected function urlImageAdmin(Produit $produit, ?string $conversion = null): ?string
    {
        if (! $produit->getFirstMedia('image_principale')) {
            return null;
        }

        $url = '/api/admin/librairie/produits/'.$produit->id.'/image';

        return $conversion !== null ? $url.'?conversion='.$conversion : $url;
    }

    protected function syntheseCommande(Commande $commande): array
    {
        return [
            'reference' => $commande->reference,
            'date' => $commande->created_at?->toIso8601String(),
            'nom_client' => $commande->nom_client,
            'prenom_client' => $commande->prenom_client,
            'telephone_client' => $commande->telephone_client,
            'whatsapp' => $commande->whatsapp,
            'statut' => $commande->statut,
            'statut_libelle' => Commande::statutLabel($commande->statut),
            'is_livraison' => (bool) $commande->is_livraison,
            'montant_total' => (float) $commande->montant_total,
            'frais_livraison' => (float) $commande->frais_livraison,
            'total_articles' => (int) ($commande->total_articles ?? $commande->lignes->sum('quantite')),
            'annulation_demandee' => $commande->annulation_demandee_at !== null,
        ];
    }
}
