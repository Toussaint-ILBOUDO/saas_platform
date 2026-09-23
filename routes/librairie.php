<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Librairie\Http\Controllers\PublicLibrairieController;
use App\Modules\Librairie\Http\Controllers\LibrairieController;
use App\Modules\Librairie\Http\Controllers\AdminLibrairieController;

/*
|--------------------------------------------------------------------------
| Routes Librairie — Public
|--------------------------------------------------------------------------
*/

Route::get('/librairie', [PublicLibrairieController::class, 'index'])
    ->name('librairie.index');

Route::get('/librairie/categories', [PublicLibrairieController::class, 'categories'])
    ->name('librairie.categories');

Route::get('/librairie/categories/{slug}', [PublicLibrairieController::class, 'categorie'])
    ->name('librairie.categorie')
    ->where('slug', '[a-z0-9\-]+');

Route::get('/librairie/produits', [PublicLibrairieController::class, 'produits'])
    ->name('librairie.produits');

Route::get('/librairie/recherche', [PublicLibrairieController::class, 'recherche'])
    ->name('librairie.recherche');

Route::get('/librairie/panier', [PublicLibrairieController::class, 'panier'])
    ->name('librairie.panier');

Route::post('/librairie/commander', [PublicLibrairieController::class, 'passerCommande'])
    ->name('librairie.commander');

/*
|--------------------------------------------------------------------------
| Confirmation & PDF de commande — accès sécurisé par jeton
|--------------------------------------------------------------------------
| Ces pages sont accessibles à un visiteur non connecté (commande guest)
| grâce à un jeton aléatoire généré à la création de la commande.
| L'identifiant séquentiel seul ne suffit plus (anti-IDOR).
| Pour un utilisateur connecté, la CommandePolicy s'applique.
|--------------------------------------------------------------------------
*/

Route::get('/librairie/commandes/{commande}/{token}/confirmation', [PublicLibrairieController::class, 'confirmation'])
    ->name('librairie.confirmation')
    ->whereNumber('commande')
    ->where('token', '[A-Za-z0-9]+');

Route::get('/librairie/commandes/{commande}/{token}/pdf', [PublicLibrairieController::class, 'pdf'])
    ->name('librairie.pdf')
    ->whereNumber('commande')
    ->where('token', '[A-Za-z0-9]+');

/*
|--------------------------------------------------------------------------
| Routes Librairie — Espace Privé (utilisateurs connectés)
|--------------------------------------------------------------------------
| Placé AVANT le catch-all /{slug} pour éviter le shadow 404
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('librairie')->name('librairie.')->group(function () {

    Route::get('/mes-commandes', [LibrairieController::class, 'index'])
        ->name('mes-commandes.index');

    Route::get('/mes-commandes/{commande}', [LibrairieController::class, 'show'])
        ->name('mes-commandes.show')
        ->whereNumber('commande');

    Route::get('/mes-commandes/{commande}/pdf', [LibrairieController::class, 'pdf'])
        ->name('mes-commandes.pdf')
        ->whereNumber('commande');
});

/*
|--------------------------------------------------------------------------
| Route publique {slug} — APRÈS les routes statiques pour éviter l'ombre
|--------------------------------------------------------------------------
*/

Route::get('/librairie/{slug}', [PublicLibrairieController::class, 'produit'])
    ->name('librairie.produit')
    ->where('slug', '[a-z0-9\-]+');

/*
|--------------------------------------------------------------------------
| Routes Librairie — Admin (Catégories, Produits, Commandes)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin|super-admin|gestionnaire'])->prefix('admin/librairie')->name('admin.librairie.')->group(function () {

    Route::get('/dashboard', [AdminLibrairieController::class, 'dashboard'])
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | CATÉGORIES
    |--------------------------------------------------------------------------
    */

    Route::get('/categories', [AdminLibrairieController::class, 'indexCategories'])
        ->name('categories.index');
    Route::get('/categories/creer', [AdminLibrairieController::class, 'createCategorie'])
        ->name('categories.create');
    Route::post('/categories', [AdminLibrairieController::class, 'storeCategorie'])
        ->name('categories.store');
    Route::get('/categories/{categorie}/modifier', [AdminLibrairieController::class, 'editCategorie'])
        ->name('categories.edit')
        ->whereNumber('categorie');
    Route::put('/categories/{categorie}', [AdminLibrairieController::class, 'updateCategorie'])
        ->name('categories.update')
        ->whereNumber('categorie');
    Route::delete('/categories/{categorie}', [AdminLibrairieController::class, 'destroyCategorie'])
        ->name('categories.destroy')
        ->whereNumber('categorie');

    /*
    |--------------------------------------------------------------------------
    | PRODUITS
    |--------------------------------------------------------------------------
    */

    Route::get('/produits', [AdminLibrairieController::class, 'indexProduits'])
        ->name('produits.index');
    Route::get('/produits/creer', [AdminLibrairieController::class, 'createProduit'])
        ->name('produits.create');
    Route::post('/produits', [AdminLibrairieController::class, 'storeProduit'])
        ->name('produits.store');
    Route::get('/produits/{produit}/modifier', [AdminLibrairieController::class, 'editProduit'])
        ->name('produits.edit')
        ->whereNumber('produit');
    Route::put('/produits/{produit}', [AdminLibrairieController::class, 'updateProduit'])
        ->name('produits.update')
        ->whereNumber('produit');
    Route::delete('/produits/{produit}', [AdminLibrairieController::class, 'destroyProduit'])
        ->name('produits.destroy')
        ->whereNumber('produit');

    /*
    |--------------------------------------------------------------------------
    | COMMANDES
    |--------------------------------------------------------------------------
    */

    Route::get('/commandes', [AdminLibrairieController::class, 'indexCommandes'])
        ->name('commandes.index');
    Route::get('/commandes/{commande}', [AdminLibrairieController::class, 'showCommande'])
        ->name('commandes.show')
        ->whereNumber('commande');
    Route::patch('/commandes/{commande}/statut', [AdminLibrairieController::class, 'changerStatut'])
        ->name('commandes.statut')
        ->whereNumber('commande');

    Route::get('/commandes/{commande}/pdf', [AdminLibrairieController::class, 'pdfCommande'])
        ->name('commandes.pdf')
        ->whereNumber('commande');
});
