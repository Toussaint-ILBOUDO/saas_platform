<?php

namespace App\Modules\Librairie\Services;

use App\Models\CategorieProduit;
use App\Models\Produit;
use App\Models\Commande;
use App\Models\LigneCommande;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class LibrairieService
{
    // =========================
    // DASHBOARD
    // =========================

    public function getDashboardStats(): array
    {
        $produits = Produit::count();
        $categories = CategorieProduit::count();
        $commandes = Commande::count();
        $commandesDuJour = Commande::duJour()->count();
        $montantTotalVentes = (float) Commande::where('statut', '!=', Commande::STATUT_ANNULEE)->sum('montant_total');
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
            'categories',
            'commandes',
            'commandesDuJour',
            'montantTotalVentes',
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

    public function getCommandesForUser(int $userId)
    {
        return Commande::forUser($userId)
            ->withTotalArticles()
            ->latest()
            ->paginate(15);
    }

    public function passCommande(array $data, array $lignes): Commande
    {
        return DB::transaction(function () use ($data, $lignes) {
            $produitIds = collect($lignes)->pluck('produit_id')->unique()->values()->all();
            $produits = Produit::whereIn('id', $produitIds)->get()->keyBy('id');

            $montantTotal = 0;
            $lignesData = [];

            foreach ($lignes as $ligne) {
                $produit = $produits->get($ligne['produit_id']);
                if (!$produit) {
                    throw new \InvalidArgumentException("Produit #{$ligne['produit_id']} introuvable.");
                }
                $quantite = max(1, (int) $ligne['quantite']);
                $sousTotal = (float) $produit->prix * $quantite;
                $montantTotal += $sousTotal;

                $lignesData[] = [
                    'produit_id' => $produit->id,
                    'quantite' => $quantite,
                    'prix_unitaire' => $produit->prix,
                    'sous_total' => $sousTotal,
                ];
            }

            $fraisLivraison = !empty($data['is_livraison']) ? (float) ($data['frais_livraison'] ?? 0) : 0;

            $commande = Commande::create([
                'user_id' => Auth::id(),
                'nom_client' => $data['nom_client'],
                'telephone_client' => $data['telephone_client'],
                'whatsapp' => $data['whatsapp'] ?? null,
                'adresse_livraison' => $data['adresse_livraison'],
                'quartier' => $data['quartier'] ?? null,
                'is_livraison' => $data['is_livraison'] ?? false,
                'montant_total' => $montantTotal,
                'frais_livraison' => $fraisLivraison,
                'token' => Str::random(64),
                'statut' => Commande::STATUT_EN_ATTENTE,
                'mode_paiement' => $data['mode_paiement'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($lignesData as $ld) {
                LigneCommande::create([
                    'commande_id' => $commande->id,
                    'produit_id' => $ld['produit_id'],
                    'quantite' => $ld['quantite'],
                    'prix_unitaire' => $ld['prix_unitaire'],
                    'sous_total' => $ld['sous_total'],
                ]);
            }

            return $commande->load('lignes.produit');
        });
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

        return $commande->fresh();
    }

    public function annulerCommande(Commande $commande): Commande
    {
        return $this->changerStatut($commande, Commande::STATUT_ANNULEE);
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
