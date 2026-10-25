<?php

namespace App\Modules\Librairie\Services;

use App\Models\CategorieProduit;
use App\Models\ParametreLibrairie;
use App\Models\Produit;
use App\Models\Commande;
use App\Models\LigneCommande;
use App\Models\EvenementFacturable;
use App\Models\User;
use App\Modules\Plateforme\Services\JournalFacturable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LibrairieService
{
    // =========================
    // DASHBOARD
    // =========================

    /**
     * Tableau de bord libraire.
     *
     * Règle financière explicite : le « chiffre d'affaires » est la somme des
     * montants produits des commandes NON annulées (frais de livraison
     * comptés à part). Aucun paiement n'est présenté comme encaissé : le
     * module ne pilote pas le paiement.
     */
    public function getDashboardStats(): array
    {
        $produits = Produit::count();
        $produitsPublies = Produit::active()->count();
        $produitsArchives = Produit::where('est_archive', true)->count();
        $produitsStockFaible = Produit::active()
            ->whereNotNull('stock')
            ->where('stock', '<=', 5)
            ->count();
        $categories = CategorieProduit::count();
        $commandes = Commande::count();
        $commandesDuJour = Commande::duJour()->count();
        $commandesParStatut = Commande::selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');

        $horsAnnulees = fn () => Commande::where('statut', '!=', Commande::STATUT_ANNULEE);

        $montantTotalVentes = (float) $horsAnnulees()->sum('montant_total');
        $montantLivraisons = (float) $horsAnnulees()->sum('frais_livraison');

        $dernieresCommandes = Commande::withTotalArticles()
            ->latest()
            ->take(10)
            ->get();
        $topProduits = LigneCommande::selectRaw('produit_id, SUM(quantite) as total_vendus')
            ->groupBy('produit_id')
            ->orderByDesc('total_vendus')
            ->take(5)
            ->with('produit.media')
            ->get()
            ->pluck('produit')
            ->filter();

        return compact(
            'produits',
            'produitsPublies',
            'produitsArchives',
            'produitsStockFaible',
            'categories',
            'commandes',
            'commandesDuJour',
            'commandesParStatut',
            'montantTotalVentes',
            'montantLivraisons',
            'dernieresCommandes',
            'topProduits'
        );
    }

    // =========================
    // CATÉGORIES
    // =========================

    public function paginateCategories(array $filters = [])
    {
        return CategorieProduit::query()
            ->withCount('produits')
            ->search($filters['search'] ?? null)
            ->ordered()
            ->paginate(15)
            ->withQueryString();
    }

    public function getCategoriesActives()
    {
        return CategorieProduit::active()->ordered()->get();
    }

    public function getCategorieBySlug(string $slug): CategorieProduit
    {
        return CategorieProduit::where('slug', $slug)->firstOrFail();
    }

    public function createCategorie(array $data): CategorieProduit
    {
        return CategorieProduit::create($data);
    }

    public function updateCategorie(CategorieProduit $categorie, array $data): CategorieProduit
    {
        $categorie->update($data);
        return $categorie->fresh();
    }

    public function deleteCategorie(CategorieProduit $categorie): bool
    {
        if ($categorie->produits()->exists()) {
            return false;
        }

        $categorie->delete();
        return true;
    }

    // =========================
    // PRODUITS
    // =========================

    public function paginateProduitsPublic(array $filters = [])
    {
        $query = Produit::query()
            ->active()
            ->with(['categorie', 'media']);

        if (!empty($filters['categorie_id'])) {
            $query->inCategorie($filters['categorie_id']);
        }

        $query->search($filters['search'] ?? null);

        if (!empty($filters['prix_min']) || !empty($filters['prix_max'])) {
            $query->prixBetween(
                $filters['prix_min'] ?? null,
                $filters['prix_max'] ?? null
            );
        }

        $sort = $filters['sort'] ?? 'recent';
        switch ($sort) {
            case 'prix_asc':
                $query->orderBy('prix', 'asc');
                break;
            case 'prix_desc':
                $query->orderBy('prix', 'desc');
                break;
            case 'nom':
                $query->orderBy('nom');
                break;
            default:
                $query->latest();
        }

        return $query->paginate(12)->withQueryString();
    }

    public function paginateAdminProduits(array $filters = [])
    {
        return Produit::query()
            ->with(['categorie', 'media'])
            ->search($filters['search'] ?? null)
            ->when(!empty($filters['categorie_id']), fn ($q) => $q->inCategorie($filters['categorie_id']))
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', (bool) $filters['is_active']))
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }

    public function getProduitBySlug(string $slug): Produit
    {
        return Produit::with(['categorie', 'media'])
            ->where('slug', $slug)
            ->active()
            ->firstOrFail();
    }

    public function getProduitById(int $id): Produit
    {
        return Produit::with(['categorie', 'media'])->findOrFail($id);
    }

    public function createProduit(array $data, ?UploadedFile $image = null): Produit
    {
        return DB::transaction(function () use ($data, $image) {
            $produit = Produit::create($data);

            if ($image) {
                $produit->addMedia($image)->toMediaCollection('image_principale');
            }

            return $produit;
        });
    }

    public function updateProduit(Produit $produit, array $data, ?UploadedFile $image = null): Produit
    {
        return DB::transaction(function () use ($produit, $data, $image) {
            $produit->update($data);

            if ($image) {
                $produit->clearMediaCollection('image_principale');
                $produit->addMedia($image)->toMediaCollection('image_principale');
            }

            return $produit->fresh();
        });
    }

    public function deleteProduit(Produit $produit): bool
    {
        return DB::transaction(function () use ($produit) {
            // Un produit déjà commandé n'est jamais supprimé : son historique
            // commercial doit rester lisible. Il est archivé (invisble du
            // catalogue public, conservé dans les commandes passées).
            if ($produit->lignesCommande()->exists()) {
                $produit->update([
                    'est_archive' => true,
                    'is_active' => false,
                ]);

                return true;
            }

            $produit->clearMediaCollection('image_principale');
            $produit->delete();

            return true;
        });
    }

    public function getProduitsSimilaires(Produit $produit, int $limit = 4)
    {
        return Produit::with('media')
            ->active()
            ->where('categorie_id', $produit->categorie_id)
            ->where('id', '!=', $produit->id)
            ->take($limit)
            ->get();
    }

    // =========================
    // COMMANDES
    // =========================

    public function paginateCommandes(array $filters = [])
    {
        return Commande::query()
            ->with(['user'])
            ->withTotalArticles()
            ->search($filters['search'] ?? null)
            ->when(!empty($filters['statut']), fn ($q) => $q->statut($filters['statut']))
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }

    public function getCommande(int $id): Commande
    {
        return Commande::with(['lignes.produit.media', 'user'])->findOrFail($id);
    }

    public function getCommandeParReference(string $reference): Commande
    {
        return Commande::with(['lignes.produit.media', 'user'])
            ->where('reference', $reference)
            ->firstOrFail();
    }

    public function getCommandesForUser(int $userId)
    {
        return Commande::forUser($userId)
            ->withTotalArticles()
            ->latest()
            ->paginate(15);
    }

    /**
     * Devis serveur : recalcule les prix officiels à partir du catalogue.
     * Le navigateur n'envoie que les identifiants et les quantités — jamais
     * un montant à faire confiance.
     */
    public function devis(array $lignes): array
    {
        $quantites = $this->regrouperLignes($lignes);
        $produits = Produit::whereIn('id', $quantites->pluck('produit_id'))
            ->active()
            ->get()
            ->keyBy('id');

        $lignesDevis = [];
        $montantTotal = 0.0;
        $disponible = true;

        foreach ($quantites as $ligne) {
            $produit = $produits->get($ligne['produit_id']);

            if (! $produit) {
                $disponible = false;
                continue;
            }

            $stockOk = $produit->estDisponible($ligne['quantite']);
            $disponible = $disponible && $stockOk;

            $prixVente = $produit->prixVente;
            $sousTotal = round($prixVente * $ligne['quantite'], 2);
            $montantTotal += $sousTotal;

            $lignesDevis[] = [
                'produit_id' => $produit->id,
                'nom' => $produit->nom,
                'slug' => $produit->slug,
                'reference' => $produit->reference,
                'image_url' => $produit->imageUrl('thumb'),
                'quantite' => $ligne['quantite'],
                'prix_unitaire' => $prixVente,
                'prix_normal' => (float) $produit->prix,
                'en_promotion' => $produit->enPromotion,
                'sous_total' => $sousTotal,
                'stock_disponible' => $produit->stock,
                'disponible' => $stockOk,
            ];
        }

        return [
            'lignes' => $lignesDevis,
            'montant_total' => round($montantTotal, 2),
            'frais_livraison' => ParametreLibrairie::fraisLivraison(),
            'disponible' => $disponible,
        ];
    }

    /**
     * Crée une commande : prix et frais recalculés en base, stock décrémenté
     * lorsque le produit est suivi, transactionnel, protégé contre les
     * doubles soumissions par `cle_idempotence`.
     */
    public function passCommande(array $data, array $lignes): Commande
    {
        return DB::transaction(function () use ($data, $lignes) {
            // Rejeu de requête / double clic : la même clé renvoie la
            // commande déjà enregistrée, sans doublon ni second décrément.
            if (! empty($data['cle_idempotence'])) {
                $existante = Commande::where('cle_idempotence', $data['cle_idempotence'])->first();

                if ($existante) {
                    return $existante->load('lignes.produit');
                }
            }

            $quantites = $this->regrouperLignes($lignes);

            $produits = Produit::whereIn('id', $quantites->pluck('produit_id'))
                ->active()
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $montantTotal = 0;
            $lignesData = [];

            foreach ($quantites as $ligne) {
                $produit = $produits->get($ligne['produit_id']);

                if (! $produit) {
                    throw ValidationException::withMessages([
                        'panier' => 'Un produit de votre panier n\'est plus disponible.',
                    ]);
                }

                if (! $produit->estDisponible($ligne['quantite'])) {
                    throw ValidationException::withMessages([
                        'panier' => 'Stock insuffisant pour « '.$produit->nom.' ».',
                    ]);
                }

                $quantite = $ligne['quantite'];
                $prixVente = $produit->prixVente;
                $sousTotal = round($prixVente * $quantite, 2);
                $montantTotal += $sousTotal;

                $lignesData[] = [
                    'produit' => $produit,
                    'quantite' => $quantite,
                    'prix_unitaire' => $prixVente,
                    'sous_total' => $sousTotal,
                ];
            }

            $isLivraison = ! empty($data['is_livraison']);

            // Frais de livraison : UNIQUEMENT ceux configurés par la
            // structure (0 par défaut). La valeur envoyée par le client est
            // volontairement ignorée.
            $fraisLivraison = $isLivraison ? ParametreLibrairie::fraisLivraison() : 0;

            $commande = Commande::create([
                'reference' => Commande::genererReference(),
                'user_id' => Auth::id(),
                'nom_client' => $data['nom_client'],
                'prenom_client' => $data['prenom_client'] ?? null,
                'telephone_client' => $data['telephone_client'],
                'whatsapp' => $data['whatsapp'] ?? null,
                // Absente en cas de retrait sur place (validation nullable).
                'adresse_livraison' => $data['adresse_livraison'] ?? null,
                'quartier' => $data['quartier'] ?? null,
                'point_de_repere' => $data['point_de_repere'] ?? null,
                'lien_geolocalisation' => $data['lien_geolocalisation'] ?? null,
                'is_livraison' => $isLivraison,
                'montant_total' => round($montantTotal, 2),
                'frais_livraison' => $fraisLivraison,
                'token' => Str::random(64),
                'cle_idempotence' => $data['cle_idempotence'] ?? null,
                'statut' => Commande::STATUT_EN_ATTENTE,
                'mode_paiement' => $data['mode_paiement'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($lignesData as $ld) {
                LigneCommande::create([
                    'commande_id' => $commande->id,
                    'produit_id' => $ld['produit']->id,
                    'quantite' => $ld['quantite'],
                    'prix_unitaire' => $ld['prix_unitaire'],
                    'sous_total' => $ld['sous_total'],
                ]);

                if ($ld['produit']->stock !== null) {
                    $ld['produit']->decrement('stock', $ld['quantite']);
                }
            }

            return $commande->load('lignes.produit');
        });
    }

    /**
     * Regroupe les lignes du panier : un même produit envoyé deux fois est
     * additionné, jamais dupliqué.
     *
     * @return \Illuminate\Support\Collection<int, array{produit_id: int, quantite: int}>
     */
    protected function regrouperLignes(array $lignes)
    {
        return collect($lignes)
            ->groupBy(fn ($ligne) => (int) $ligne['produit_id'])
            ->map(fn ($groupe, $produitId) => [
                'produit_id' => $produitId,
                'quantite' => max(1, (int) $groupe->sum(fn ($ligne) => (int) $ligne['quantite'])),
            ])
            ->values();
    }

    /**
     * Suivi PUBLIC d'une commande par sa référence : strictement limité aux
     * informations dont le client a besoin — aucune donnée d'un autre
     * client, aucune note interne, aucun chiffre d'affaires.
     */
    public function suivreCommande(string $reference): array
    {
        $commande = $this->getCommandeParReference($reference);

        $derniereMaj = collect($commande->statut_modifications ?? [])->max('created_at')
            ?: $commande->updated_at?->toIso8601String();

        return [
            'reference' => $commande->reference,
            'date' => $commande->created_at?->toIso8601String(),
            'statut' => $commande->statut,
            'statut_libelle' => Commande::statutLabel($commande->statut),
            'is_livraison' => (bool) $commande->is_livraison,
            'quartier' => $commande->quartier,
            'nom_client' => $commande->nom_client,
            'prenom_client' => $commande->prenom_client,
            'montant_total' => (float) $commande->montant_total,
            'frais_livraison' => (float) $commande->frais_livraison,
            'etapes' => $commande->etapesSuivi(),
            'derniere_maj' => $derniereMaj,
            'annulation_demandee' => $commande->annulation_demandee_at !== null,
            'annulable' => $commande->estAnnulableParClient(),
            'lignes' => $commande->lignes->map(fn ($ligne) => [
                'nom' => $ligne->produit?->nom,
                'quantite' => $ligne->quantite,
                'prix_unitaire' => (float) $ligne->prix_unitaire,
                'sous_total' => (float) $ligne->sous_total,
            ]),
        ];
    }

    /**
     * Annulation demandée par le client.
     *
     *  - `en_attente` : rien n'est engagé, la commande est annulée d'office.
     *  - `confirmee` : simple demande tracée, le personnel la valide.
     *  - au-delà (préparation, livrée) : refus, la commande est engagée.
     */
    public function demanderAnnulation(Commande $commande, ?string $motif = null): Commande
    {
        if (! $commande->estAnnulableParClient()) {
            throw ValidationException::withMessages([
                'commande' => 'Cette commande ne peut plus être annulée : elle est déjà en préparation ou terminée.',
            ]);
        }

        if ($commande->estEnAttente()) {
            $commande->update(['annulation_motif' => $motif ?? 'Annulée à la demande du client.']);

            return $this->changerStatut($commande, Commande::STATUT_ANNULEE, Auth::id());
        }

        $commande->update([
            'annulation_demandee_at' => now(),
            'annulation_motif' => $motif,
        ]);

        return $commande->fresh();
    }

    /**
     * Annulation validée par le personnel (ou l'admin).
     */
    public function annulerCommande(Commande $commande, ?int $modifiePar = null): Commande
    {
        $commande->update(['annulation_demandee_at' => $commande->annulation_demandee_at ?? now()]);

        return $this->changerStatut($commande, Commande::STATUT_ANNULEE, $modifiePar);
    }

    public function changerStatut(Commande $commande, string $nouveauStatut, ?int $modifiePar = null): Commande
    {
        $transitions = [
            Commande::STATUT_EN_ATTENTE => [
                Commande::STATUT_CONFIRMEE,
                Commande::STATUT_ANNULEE,
            ],
            Commande::STATUT_CONFIRMEE => [
                Commande::STATUT_EN_PREPARATION,
                Commande::STATUT_ANNULEE,
            ],
            Commande::STATUT_EN_PREPARATION => [
                Commande::STATUT_LIVREE,
            ],
        ];

        $transitionsValides = $transitions[$commande->statut] ?? [];

        if (!in_array($nouveauStatut, $transitionsValides)) {
            throw new \InvalidArgumentException(
                "Transition de statut invalide : {$commande->statut} → {$nouveauStatut}"
            );
        }

        $ancienStatut = $commande->statut;
        $commande->update(['statut' => $nouveauStatut]);
        $commande->enregistrerModificationStatut($ancienStatut, $nouveauStatut, $modifiePar);

        // P8 : journalisation facturable — vente « livrée » (Q-A15) ;
        // assiette = montant_total sans les frais de livraison.
        if ($nouveauStatut === Commande::STATUT_LIVREE && $ancienStatut !== Commande::STATUT_LIVREE) {
            JournalFacturable::capturer(
                EvenementFacturable::TYPE_VENTE_LIBRAIRIE,
                'commande',
                $commande->id,
                'Vente librairie CMD-'.$commande->reference,
                (int) round((float) $commande->montant_total),
            );
        }

        return $commande->fresh();
    }

    // =========================
    // STATS PUBLIQUES
    // =========================

    public function getHomepageStats(): array
    {
        return [
            'total_produits' => Produit::active()->count(),
            'total_categories' => CategorieProduit::active()->count(),
        ];
    }

    public function getCategoriesWithProduitsCount()
    {
        return CategorieProduit::active()
            ->withCount(['produits as produits_actifs_count' => fn ($q) => $q->where('is_active', true)])
            ->ordered()
            ->get();
    }
}
