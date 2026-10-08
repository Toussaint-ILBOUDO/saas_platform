<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Finance\Http\Controllers\BulletinPaieController;
use App\Modules\Finance\Http\Controllers\Enseignant\BulletinPaieController as EnseignantBulletinPaieController;
use App\Modules\Finance\Http\Controllers\FactureWebController;
use App\Modules\Finance\Http\Controllers\FactureCabinetController;
use App\Modules\Finance\Http\Controllers\PeriodeComptableControllerWeb;
use App\Modules\Finance\Http\Controllers\TypeAjustementController;

Route::middleware(['auth', 'role:admin_cabinet'])
    ->prefix('finance')
    ->name('finance.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | PÉRIODES COMPTABLES
        |--------------------------------------------------------------------------
        */

        // D-051 : pas de `destroy` — une période ne se supprime pas
        // (elle porte le rattachement de ses rapports, factures et bulletins).
        Route::resource('periodes', PeriodeComptableControllerWeb::class)
            ->except(['destroy']);

        Route::patch(
            'periodes/{periode}/close',
            [PeriodeComptableControllerWeb::class, 'close']
        )->name('periodes.close');

        // D-051 : réouverture, pour corriger une clôture erronée. Le service
        // la refuse s'il existe déjà des factures ou bulletins sur la période.
        Route::patch(
            'periodes/{periode}/reopen',
            [PeriodeComptableControllerWeb::class, 'reopen']
        )->name('periodes.reopen');

        /*
        |--------------------------------------------------------------------------
        | FACTURES PARENTS — routes custom AVANT la resource
        |--------------------------------------------------------------------------
        */

        Route::get(
            'factures/verifier-prerequis',
            [FactureWebController::class, 'verifierPrerequis']
        )->name('factures.verifier-prerequis');

        Route::post(
            'factures/preview',
            [FactureWebController::class, 'preview']
        )->name('factures.preview');

        Route::resource('factures', FactureWebController::class)
            ->only(['index', 'create', 'store']);

        Route::get(
            'factures/{facture}/payer',
            [FactureWebController::class, 'payer']
        )->name('factures.payer');

        Route::post(
            'factures/{facture}/marquer-paye',
            [FactureWebController::class, 'marquerPaye']
        )->name('factures.marquer-paye');

        /*
        |--------------------------------------------------------------------------
        | BULLETINS DE PAIE — routes custom AVANT la resource
        |--------------------------------------------------------------------------
        */

        Route::get(
            'bulletins-paie/preview',
            [BulletinPaieController::class, 'preview']
        )->name('bulletins-paie.preview');

        Route::post(
            'bulletins-paie/preview/actualiser',
            [BulletinPaieController::class, 'actualiser']
        )->name('bulletins-paie.preview.actualiser');

        Route::post(
            'bulletins-paie/generer',
            [BulletinPaieController::class, 'generer']
        )->name('bulletins-paie.generer');

        Route::get(
            'bulletins-paie/{bulletin}/payer',
            [BulletinPaieController::class, 'payer']
        )->name('bulletins-paie.payer');

        Route::post(
            'bulletins-paie/{bulletin}/marquer-paye',
            [BulletinPaieController::class, 'marquerPaye']
        )->name('bulletins-paie.marquer-paye');

        Route::get(
            'bulletins-paie/{bulletin}/pdf',
            [BulletinPaieController::class, 'pdf']
        )->name('bulletins-paie.pdf');

        Route::get(
            'bulletins-paie/{bulletin}/pdf/download',
            [BulletinPaieController::class, 'pdfDownload']
        )->name('bulletins-paie.pdf.download');

        Route::get(
            'bulletins-paie/{bulletin}/ajouter-ajustement',
            [BulletinPaieController::class, 'createAjustement']
        )->name('bulletins-paie.ajustement.create');

        Route::post(
            'bulletins-paie/{bulletin}/ajustement',
            [BulletinPaieController::class, 'storeAjustement']
        )->name('bulletins-paie.ajustement.store');

        Route::delete(
            'bulletins-paie/{bulletin}/ajustement/{ajustement}',
            [BulletinPaieController::class, 'destroyAjustement']
        )->name('bulletins-paie.ajustement.destroy');

        Route::post(
            'bulletins-paie/{bulletin}/consulter',
            [BulletinPaieController::class, 'marquerConsulte']
        )->name('bulletins-paie.consulter');

        Route::post(
            'bulletins-paie/{bulletin}/valider',
            [BulletinPaieController::class, 'valider']
        )->name('bulletins-paie.valider');

        Route::post(
            'bulletins-paie/{bulletin}/corriger',
            [BulletinPaieController::class, 'corriger']
        )->name('bulletins-paie.corriger');

        Route::resource('bulletins-paie', BulletinPaieController::class)
            ->parameters([
                'bulletins-paie' => 'bulletin'
            ])
            ->only(['index', 'show']);
        /*
        |--------------------------------------------------------------------------
        | TYPES D'AJUSTEMENTS
        |--------------------------------------------------------------------------
        */

        Route::resource(
            'type-ajustements',
            TypeAjustementController::class
        )->except(['show']);

        /*
        |--------------------------------------------------------------------------
        | FACTURATION CABINET — routes custom AVANT la resource
        |--------------------------------------------------------------------------
        */

        Route::get(
            'facture-cabinet/preview',
            [FactureCabinetController::class, 'preview']
        )->name('facture-cabinet.preview');

        Route::resource('facture-cabinet', FactureCabinetController::class)
            ->except(['edit', 'update', 'destroy', 'store']);

        Route::get(
            'facture-cabinet/create',
            [FactureCabinetController::class, 'create']
        )->name('facture-cabinet.create')
            ->middleware('role:super-admin');

        Route::post(
            'facture-cabinet',
            [FactureCabinetController::class, 'store']
        )->name('facture-cabinet.store')
            ->middleware('role:super-admin');

        Route::get(
            'facture-cabinet/{facture}/payer',
            [FactureCabinetController::class, 'payer']
        )->name('facture-cabinet.payer');

        Route::post(
            'facture-cabinet/{facture}/paiement',
            [FactureCabinetController::class, 'storePaiement']
        )->name('facture-cabinet.paiement.store');

        Route::post(
            'paiement-cabinet/{paiement}/valider',
            [FactureCabinetController::class, 'validerPaiement']
        )->name('paiement-cabinet.valider');

        Route::post(
            'paiement-cabinet/{paiement}/annuler',
            [FactureCabinetController::class, 'annulerPaiement']
        )->name('paiement-cabinet.annuler');

        Route::post(
            'facture-cabinet/{facture}/annuler',
            [FactureCabinetController::class, 'annuler']
        )->name('facture-cabinet.annuler');

        Route::get(
            'facture-cabinet/{facture}/pdf',
            [FactureCabinetController::class, 'pdf']
        )->name('facture-cabinet.pdf');
    });

/*
|--------------------------------------------------------------------------
| CONSULTATION FACTURE — admin ET parent concerné (policy FacturePolicy)
|--------------------------------------------------------------------------
| show / pdf / pdf.download sont Accessibles à l'admin ET au parent
| propriétaire de la facture (le lien de notification 'facture' pointe
| vers finance.factures.show). Le reste (création, paiement) reste admin.
|--------------------------------------------------------------------------
*/

Route::middleware('auth')
    ->prefix('finance')
    ->name('finance.')
    ->controller(FactureWebController::class)
    ->group(function () {
        Route::get('factures/{facture}', 'show')
            ->name('factures.show')
            ->can('view', 'facture');

        Route::get('factures/{facture}/pdf', 'pdf')
            ->name('factures.pdf')
            ->can('view', 'facture');

        Route::get('factures/{facture}/pdf/download', 'pdfDownload')
            ->name('factures.pdf.download')
            ->can('view', 'facture');
    });

/*
|--------------------------------------------------------------------------
| MES FACTURES — espace parent
|--------------------------------------------------------------------------
| Liste des factures du parent connecté (statut + filtre enfant),
| construite scrupuleusement sur parent_id (pas de permission facture.view).
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:parent'])
    ->prefix('mes-factures')
    ->name('mes-factures.')
    ->controller(FactureWebController::class)
    ->group(function () {
        Route::get('/', 'mesFactures')->name('index');
    });

/*
|--------------------------------------------------------------------------
| MES BULLETINS DE PAIE — Espace enseignant
|--------------------------------------------------------------------------
*/

Route::middleware('auth')
    ->prefix('mes-bulletins')
    ->name('mes-bulletins.')
    ->controller(EnseignantBulletinPaieController::class)
    ->group(function () {

        Route::get('/', 'index')
            ->name('index')
            ->can('viewAny', \App\Models\BulletinPaie::class);

        Route::get('/{bulletin}', 'show')
            ->name('show')
            ->can('view', 'bulletin');

        Route::get('/{bulletin}/pdf', 'pdf')
            ->name('pdf')
            ->can('view', 'bulletin');

        Route::get('/{bulletin}/pdf/download', 'pdfDownload')
            ->name('pdf.download')
            ->can('view', 'bulletin');

        Route::post('/{bulletin}/consulter', 'marquerConsulte')
            ->name('consulter')
            ->can('update', 'bulletin');

        Route::post('/{bulletin}/valider', 'valider')
            ->name('valider')
            ->can('update', 'bulletin');

        Route::get('/{bulletin}/contester', 'contesterForm')
            ->name('contester.form')
            ->can('update', 'bulletin');

        Route::post('/{bulletin}/contester', 'contester')
            ->name('contester')
            ->can('update', 'bulletin');

        // D-052 — l'enseignant confirme avoir reçu son paiement hors plateforme.
        Route::post('/{bulletin}/confirmer-reception', 'confirmerReception')
            ->name('confirmer-reception')
            ->can('update', 'bulletin');
    });
