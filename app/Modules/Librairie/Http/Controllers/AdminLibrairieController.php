<?php

namespace App\Modules\Librairie\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Librairie\Services\LibrairieService;
use App\Modules\Librairie\Services\CommandePdfService;
use App\Modules\Librairie\Http\Requests\StoreCategorieProduitRequest;
use App\Modules\Librairie\Http\Requests\UpdateCategorieProduitRequest;
use App\Modules\Librairie\Http\Requests\StoreProduitRequest;
use App\Modules\Librairie\Http\Requests\UpdateProduitRequest;
use App\Modules\Librairie\Http\Requests\FilterProduitRequest;
use App\Modules\Librairie\Http\Requests\FilterCommandeRequest;
use App\Modules\Librairie\Http\Requests\UpdateCommandeStatutRequest;
use App\Modules\Systeme\Services\NotificationDispatcher;
use App\Modules\Systeme\Services\WhatsAppService;
use App\Models\CategorieProduit;
use App\Models\Produit;
use App\Models\Commande;

class AdminLibrairieController extends Controller
{
    public function __construct(
        protected LibrairieService $service,
        protected NotificationDispatcher $dispatcher
    ) {}

    // =========================
    // DASHBOARD
    // =========================

    public function dashboard()
    {
        $this->authorize('manage', Commande::class);

        $stats = $this->service->getDashboardStats();

        return view('librairie.dashboard', $stats);
    }

    // =========================
    // CATÉGORIES
    // =========================

    public function indexCategories()
    {
        $this->authorize('manage', Commande::class);

        $categories = $this->service->paginateCategories(request()->only('search'));

        return view('librairie.categories.index', compact('categories'));
    }

    public function createCategorie()
    {
        $this->authorize('manage', Commande::class);

        return view('librairie.categories.create');
    }

    public function storeCategorie(StoreCategorieProduitRequest $request)
    {
        $this->authorize('manage', Commande::class);

        $this->service->createCategorie($request->validated());

        return redirect()
            ->route('admin.librairie.categories.index')
            ->with('success', 'Catégorie créée avec succès.');
    }

    public function editCategorie(CategorieProduit $categorie)
    {
        $this->authorize('manage', Commande::class);

        return view('librairie.categories.edit', compact('categorie'));
    }

    public function updateCategorie(UpdateCategorieProduitRequest $request, CategorieProduit $categorie)
    {
        $this->authorize('manage', Commande::class);

        $this->service->updateCategorie($categorie, $request->validated());

        return redirect()
            ->route('admin.librairie.categories.index')
            ->with('success', 'Catégorie mise à jour avec succès.');
    }

    public function destroyCategorie(CategorieProduit $categorie)
    {
        $this->authorize('manage', Commande::class);

        $result = $this->service->deleteCategorie($categorie);

        if (!$result) {
            return redirect()
                ->route('admin.librairie.categories.index')
                ->with('error', 'Impossible de supprimer une catégorie contenant des produits.');
        }

        return redirect()
            ->route('admin.librairie.categories.index')
            ->with('success', 'Catégorie supprimée avec succès.');
    }

    // =========================
    // PRODUITS
    // =========================

    public function indexProduits(FilterProduitRequest $request)
    {
        $this->authorize('manage', Commande::class);

        $filters = $request->validated();
        $produits = $this->service->paginateAdminProduits($filters);
        $categories = $this->service->getCategoriesActives();

        return view('librairie.produits.index', compact('produits', 'categories', 'filters'));
    }

    public function createProduit()
    {
        $this->authorize('manage', Commande::class);

        $categories = $this->service->getCategoriesActives();

        return view('librairie.produits.create', compact('categories'));
    }

    public function storeProduit(StoreProduitRequest $request)
    {
        $this->authorize('manage', Commande::class);

        $this->service->createProduit(
            $request->validated(),
            $request->file('image')
        );

        return redirect()
            ->route('admin.librairie.produits.index')
            ->with('success', 'Produit créé avec succès.');
    }

    public function editProduit(Produit $produit)
    {
        $this->authorize('manage', Commande::class);

        $produit->load('categorie');
        $categories = $this->service->getCategoriesActives();

        return view('librairie.produits.edit', compact('produit', 'categories'));
    }

    public function updateProduit(UpdateProduitRequest $request, Produit $produit)
    {
        $this->authorize('manage', Commande::class);

        $this->service->updateProduit(
            $produit,
            $request->validated(),
            $request->file('image')
        );

        return redirect()
            ->route('admin.librairie.produits.index')
            ->with('success', 'Produit mis à jour avec succès.');
    }

    public function destroyProduit(Produit $produit)
    {
        $this->authorize('manage', Commande::class);

        $this->service->deleteProduit($produit);

        return redirect()
            ->route('admin.librairie.produits.index')
            ->with('success', 'Produit supprimé avec succès.');
    }

    // =========================
    // COMMANDES
    // =========================

    public function indexCommandes(FilterCommandeRequest $request)
    {
        $this->authorize('manage', Commande::class);

        $filters = $request->validated();
        $commandes = $this->service->paginateCommandes($filters);

        return view('librairie.commandes.index', compact('commandes', 'filters'));
    }

    public function showCommande(Commande $commande, WhatsAppService $whatsApp)
    {
        $this->authorize('view', $commande);

        $commande->load('lignes.produit.media', 'user');

        $whatsappLink = $whatsApp->waLink($commande);

        return view('librairie.commandes.show', compact('commande', 'whatsappLink'));
    }

    public function pdfCommande(Commande $commande, CommandePdfService $pdfService)
    {
        $this->authorize('view', $commande);

        $commande->load('lignes.produit');

        return $pdfService->download($commande);
    }

    public function changerStatut(Commande $commande, UpdateCommandeStatutRequest $request)
    {
        $this->authorize('update', $commande);

        $nouveauStatut = $request->validated()['statut'];

        try {
            $commande = $this->service->changerStatut(
                $commande,
                $nouveauStatut,
                auth()->id()
            );

            $this->dispatcher->commandeStatutChange($commande, $nouveauStatut);

            return redirect()
                ->route('admin.librairie.commandes.show', $commande)
                ->with('success', 'Statut mis à jour avec succès.');
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('admin.librairie.commandes.show', $commande)
                ->with('error', $e->getMessage());
        }
    }
}
