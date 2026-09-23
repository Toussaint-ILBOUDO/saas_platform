<?php

namespace App\Modules\Librairie\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Librairie\Services\LibrairieService;
use App\Modules\Librairie\Services\CommandePdfService;
use App\Modules\Librairie\Http\Requests\FilterProduitRequest;
use App\Modules\Librairie\Http\Requests\PasserCommandeRequest;
use App\Models\CategorieProduit;
use App\Models\Produit;
use App\Models\Commande;
use App\Modules\Systeme\Services\NotificationDispatcher;
use Illuminate\Http\Request;

class PublicLibrairieController extends Controller
{
    public function __construct(
        protected LibrairieService $service,
        protected NotificationDispatcher $dispatcher
    ) {}

    public function index()
    {
        $categories = $this->service->getCategoriesWithProduitsCount();
        $stats = $this->service->getHomepageStats();
        $produits = $this->service->paginateProduitsPublic(['sort' => 'recent']);

        return view('publicpages.pages.librairie', compact('categories', 'stats', 'produits'));
    }

    public function categories()
    {
        $categories = $this->service->getCategoriesWithProduitsCount();

        return view('publicpages.pages.librairie-categories', compact('categories'));
    }

    public function categorie(string $slug)
    {
        $categorie = $this->service->getCategorieBySlug($slug);

        $produits = $this->service->paginateProduitsPublic([
            'categorie_id' => $categorie->id,
            'sort' => request('sort', 'recent'),
        ]);

        return view('publicpages.pages.librairie-categorie', compact('categorie', 'produits'));
    }

    public function produits(FilterProduitRequest $request)
    {
        $filters = $request->validated();
        $produits = $this->service->paginateProduitsPublic($filters);
        $categories = $this->service->getCategoriesActives();

        return view('publicpages.pages.librairie-produits', compact('produits', 'categories', 'filters'));
    }

    public function produit(string $slug)
    {
        $produit = $this->service->getProduitBySlug($slug);
        $produitsSimilaires = $this->service->getProduitsSimilaires($produit);

        return view('publicpages.pages.librairie-produit', compact('produit', 'produitsSimilaires'));
    }

    public function recherche(Request $request)
    {
        $query = $request->input('q', '');
        $hasFilters = !empty($query) || $request->hasAny(['categorie_id', 'sort']);

        if (!$hasFilters) {
            return redirect()->route('librairie.produits');
        }

        $filters = array_filter([
            'search' => $query,
            'categorie_id' => $request->input('categorie_id'),
            'sort' => $request->input('sort'),
        ]);

        $produits = $this->service->paginateProduitsPublic($filters);
        $categories = $this->service->getCategoriesActives();

        return view('publicpages.pages.librairie-produits', compact('produits', 'categories', 'filters'));
    }

    public function panier()
    {
        return view('publicpages.pages.librairie-panier');
    }

    public function passerCommande(PasserCommandeRequest $request)
    {
        $data = $request->validated();
        $lignes = $data['panier'];
        unset($data['panier']);

        $commande = $this->service->passCommande($data, $lignes);

        $this->dispatcher->newOrder($commande);

        return redirect()
            ->route('librairie.confirmation', [
                'commande' => $commande,
                'token' => $commande->token,
            ])
            ->with('success', 'Votre commande a été passée avec succès !');
    }

    public function confirmation(Commande $commande, string $token, Request $request)
    {
        $this->authorizeCommandeAccess($commande, $token, $request);

        $commande->load('lignes.produit');

        return view('publicpages.pages.librairie-confirmation', compact('commande'));
    }

    public function pdf(Commande $commande, string $token, Request $request, CommandePdfService $pdfService)
    {
        $this->authorizeCommandeAccess($commande, $token, $request);

        $commande->load('lignes.produit');

        return $pdfService->download($commande);
    }

    /**
     * Autorise l'accès à une commande (confirmation / PDF).
     *
     * - Utilisateur connecté : réutilisation de la CommandePolicy existante
     *   (propriétaire de la commande, admin, gestionnaire, super-admin).
     * - Visiteur non connecté (commande guest) : accès par jeton aléatoire
     *   unique (lien non devinable). L'identifiant seul ne suffit plus.
     */
    private function authorizeCommandeAccess(
        Commande $commande,
        string $token,
        Request $request
    ): void {
        if ($request->user()) {
            $this->authorize('view', $commande);

            return;
        }

        abort_unless(
            $commande->token !== null && hash_equals($commande->token, $token),
            404
        );
    }
}
