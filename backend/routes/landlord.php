<?php

use App\Modules\Landlord\Controllers\AuthenticatedSessionController;
use App\Modules\Landlord\Controllers\CabinetController;
use App\Modules\Landlord\Controllers\CataloguePlateformeController;
use App\Modules\Landlord\Controllers\ContratCabinetController;
use App\Modules\Landlord\Controllers\DashboardController;
use App\Modules\Landlord\Controllers\FacturationCabinetController;
use App\Modules\Landlord\Controllers\JournalController;
use App\Modules\Landlord\Controllers\ParametresController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes Landlord (domaine central de la plateforme)
|--------------------------------------------------------------------------
|
| Réservées au super admin de la plateforme (D-006 : admin.localhost).
| Aucune donnée de cabinet n'est exposée ici. Les routes sont préfixées
| « /admin » : « / » seul appartient aux cabinets (routes/tenant.php).
|
*/

Route::prefix('admin')->name('landlord.')->middleware('central.domain')->group(function () {
    Route::middleware('landlord.guest')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    });

    Route::middleware('landlord.auth')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

        // Cabinets (T2.3)
        Route::get('cabinets', [CabinetController::class, 'index'])->name('cabinets.index');
        Route::get('cabinets/create', [CabinetController::class, 'create'])->name('cabinets.create');
        Route::post('cabinets', [CabinetController::class, 'store'])->name('cabinets.store');
        Route::get('cabinets/{cabinet}', [CabinetController::class, 'show'])->name('cabinets.show');
        Route::get('cabinets/{cabinet}/edit', [CabinetController::class, 'edit'])->name('cabinets.edit');
        Route::put('cabinets/{cabinet}', [CabinetController::class, 'update'])->name('cabinets.update');
        Route::put('cabinets/{cabinet}/parametres', [CabinetController::class, 'updateParametres'])->name('cabinets.parametres.update');
        Route::post('cabinets/{cabinet}/statut', [CabinetController::class, 'changeStatut'])->name('cabinets.statut');
        Route::post('cabinets/{cabinet}/impersoner', [CabinetController::class, 'impersoner'])->name('cabinets.impersoner');
        Route::delete('cabinets/{cabinet}', [CabinetController::class, 'supprimer'])->name('cabinets.destroy');

        // Journal (T2.7)
        Route::get('journal', [JournalController::class, 'index'])->name('journal.index');

        // Paramètres de la plateforme (T2.10)
        Route::get('parametres', [ParametresController::class, 'index'])->name('parametres.index');
        Route::put('parametres', [ParametresController::class, 'update'])->name('parametres.update');

        // Contrats commerciaux de cabinets (P8)
        Route::get('cabinets/{cabinet}/contrats', [ContratCabinetController::class, 'index'])->name('contrats.index');
        Route::post('cabinets/{cabinet}/contrats', [ContratCabinetController::class, 'store'])->name('contrats.store');
        Route::get('cabinets/{cabinet}/contrats/{contrat}', [ContratCabinetController::class, 'show'])->name('contrats.show');
        Route::put('cabinets/{cabinet}/contrats/{contrat}', [ContratCabinetController::class, 'update'])->name('contrats.update');
        Route::post('cabinets/{cabinet}/contrats/{contrat}/valider', [ContratCabinetController::class, 'valider'])->name('contrats.valider');
        Route::get('cabinets/{cabinet}/contrats/{contrat}/pdf', [ContratCabinetController::class, 'pdf'])->name('contrats.pdf');
        Route::delete('cabinets/{cabinet}/contrats/{contrat}', [ContratCabinetController::class, 'destroy'])->name('contrats.destroy');

        // Facturation des cabinets (P8) : brouillon → émission → paiement
        Route::get('facturation', [FacturationCabinetController::class, 'index'])->name('facturation.index');
        Route::get('facturation/preparer', [FacturationCabinetController::class, 'preparer'])->name('facturation.preparer');
        Route::post('facturation', [FacturationCabinetController::class, 'creerBrouillon'])->name('facturation.creer');
        Route::get('facturation/{facture}', [FacturationCabinetController::class, 'show'])->name('facturation.show');
        Route::post('facturation/{facture}/emettre', [FacturationCabinetController::class, 'emettre'])->name('facturation.emettre');
        Route::post('facturation/{facture}/annuler', [FacturationCabinetController::class, 'annuler'])->name('facturation.annuler');
        Route::post('facturation/{facture}/lignes', [FacturationCabinetController::class, 'ajouterLigne'])->name('facturation.lignes.ajouter');
        Route::delete('facturation/{facture}/lignes/{ligneId}', [FacturationCabinetController::class, 'supprimerLigne'])->name('facturation.lignes.supprimer');
        Route::post('facturation/{facture}/paiements', [FacturationCabinetController::class, 'ajouterPaiement'])->name('facturation.paiements.ajouter');
        Route::delete('facturation/{facture}', [FacturationCabinetController::class, 'supprimerBrouillon'])->name('facturation.brouillon.supprimer');
        Route::get('facturation/{facture}/pdf', [FacturationCabinetController::class, 'pdf'])->name('facturation.pdf');

        // Catalogue éditable de la plateforme : services facturables + lignes types (P8)
        Route::get('catalogue', [CataloguePlateformeController::class, 'index'])->name('catalogue.index');
        Route::put('catalogue/services/{service}', [CataloguePlateformeController::class, 'majService'])->name('catalogue.services.update');
        Route::post('catalogue/lignes-types', [CataloguePlateformeController::class, 'creerLigneType'])->name('catalogue.lignes-types.creer');
        Route::put('catalogue/lignes-types/{ligne}', [CataloguePlateformeController::class, 'majLigneType'])->name('catalogue.lignes-types.update');
        Route::delete('catalogue/lignes-types/{ligne}', [CataloguePlateformeController::class, 'supprimerLigneType'])->name('catalogue.lignes-types.desactiver');
    });
});