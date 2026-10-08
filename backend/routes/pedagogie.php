<?php

use Illuminate\Support\Facades\Route;

use App\Models\RapportMensuelEnseignant;
use App\Models\ContratCours;
use App\Modules\Pedagogie\Http\Controllers\ClasseController;
use App\Modules\Pedagogie\Http\Controllers\MatiereController;
use App\Modules\Pedagogie\Http\Controllers\TypeCoursController;
use App\Modules\Pedagogie\Http\Controllers\DemandeCoursAdminController;
use App\Modules\Pedagogie\Http\Controllers\ContratCoursWebController;
use App\Modules\Pedagogie\Http\Controllers\CahierTexteWebController; 
use App\Modules\Pedagogie\Http\Controllers\CahierTextePdfController;  
use App\Modules\Pedagogie\Http\Controllers\DocumentController;  
use App\Modules\Pedagogie\Http\Controllers\RapportMensuelWebController;
use App\Modules\Pedagogie\Http\Controllers\RapportMensuelPdfController;
use App\Models\ObjectifPedagogique;
use App\Modules\Pedagogie\Http\Controllers\ObjectifPedagogiqueController;
use App\Modules\Pedagogie\Http\Controllers\PlanningController;
use App\Modules\Pedagogie\Http\Controllers\PlanningEnseignantController;
use App\Modules\Pedagogie\Http\Controllers\EvaluationsController;

Route::middleware(['auth', 'role:admin_cabinet'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | RESSOURCES PEDAGOGIQUES
    |--------------------------------------------------------------------------
    */

    Route::resource('classes', ClasseController::class);

    Route::resource('matieres', MatiereController::class);

    Route::resource('type-cours', TypeCoursController::class)
        ->except(['show', 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | DEMANDES COURS (PIPELINE)
    |--------------------------------------------------------------------------
    */

    Route::prefix('demande-cours')
        ->name('demande-cours.')
        ->controller(DemandeCoursAdminController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('{demandeCours}', 'show')->name('show');
            Route::post('{demandeCours}/valider', 'valider')->name('valider');
        });

    /*
    |--------------------------------------------------------------------------
    | CONTRATS — actions réservées à l'admin (liste globale, création)
    |--------------------------------------------------------------------------
    */

    Route::prefix('contrats')
        ->name('contrats.')
        ->controller(ContratCoursWebController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
        });

    Route::get('/matieres/{matiere}/enseignants', [ContratCoursWebController::class, 'enseignantsParMatiere']);

    /*
    |--------------------------------------------------------------------------
    | TYPE COURS ACTIONS
    |--------------------------------------------------------------------------
    */

    Route::prefix('type-cours')
        ->name('type-cours.')
        ->group(function () {
            Route::patch('{typeCour}/activate', [TypeCoursController::class, 'activate'])->name('activate');
            Route::patch('{typeCour}/deactivate', [TypeCoursController::class, 'deactivate'])->name('deactivate');
        });

});

/*
|--------------------------------------------------------------------------
| CONTRATS — consultation d'un contrat précis
|
| Sorti du groupe role:admin_cabinet : l'accès à UN contrat doit être
| tranché par ContratCoursPolicy (admin, parent du contrat, enseignant
| affecté), pas par un rôle global. C'est ce qui causait le 403 sur
| /contrats/{id} pour les parents/enseignants pourtant autorisés par
| la policy.
|--------------------------------------------------------------------------
*/

Route::middleware('auth')
    ->prefix('contrats')
    ->name('contrats.')
    ->controller(ContratCoursWebController::class)
    ->group(function () {
        Route::get('{contrat}', 'show')
            ->name('show')
            ->can('view', 'contrat');
    });

/*
|--------------------------------------------------------------------------
| MES COURS — espace enseignant & élève
|
| - Un enseignant consulte ici UNIQUEMENT les contrats auxquels il est
|   affecté (filtrage via ContratCoursQueryService::paginateForEnseignant).
| - Un élève consulte UNIQUEMENT ses propres contrats (paginateForEleve).
| - Admins/super-admins restent sur /contrats (vue globale).
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:enseignant|eleve'])
    ->prefix('mes-cours')
    ->name('mes-cours.')
    ->controller(ContratCoursWebController::class)
    ->group(function () {
        Route::get('/', 'mesCours')->name('index');
    });

Route::prefix('cahiers-textes')
    ->name('cahiers-textes.')
    ->middleware('auth')
    ->controller(CahierTexteWebController::class)
    ->group(function () {

        // =========================
        // CRUD CAHIERS DE TEXTE
        // =========================
        Route::get('/', 'index')->name('index');

        // STEP 1 : choix élève
        Route::get('/create', 'createSelectEleve')->name('create.select-eleve');

        // STEP 2 : création pour un élève
        Route::get('/create/{eleve}', 'create')->name('create.by-eleve');

        Route::post('/', 'store')->name('store');

        Route::get('/{cahier}', 'show')->name('show');

        Route::get('/{cahier}/edit', 'edit')->name('edit');

        Route::put('/{cahier}', 'update')->name('update');
    });

/*
|--------------------------------------------------------------------------
| PLANNING — espace élève
|--------------------------------------------------------------------------
| Semaine (lun → dim) des séances de l'élève, construite sur les cahiers
| de texte de ses propres contrats (getWeekForEleve).
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:eleve|parent'])
    ->prefix('planning')
    ->name('planning.')
    ->controller(PlanningController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
    });

/*
|--------------------------------------------------------------------------
| PLANNING ENSEIGNANT — créneaux réguliers
|--------------------------------------------------------------------------
| L'enseignant planifie des créneaux récurrents (ex. tous les mercredis
| 18h-19h) rattachés à ses affectations (élève + matière). Ces créneaux
| sont visibles par l'élève, ses parents et les autres enseignants qui
| interviennent auprès du même élève (PlanningEnseignantController).
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:enseignant'])
    ->prefix('planning-enseignant')
    ->name('planning-enseignant.')
    ->controller(PlanningEnseignantController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{planningCours}/modifier', 'edit')->name('edit');
        Route::put('/{planningCours}', 'update')->name('update');
        Route::delete('/{planningCours}', 'destroy')->name('destroy');
    });

/*
|--------------------------------------------------------------------------
| ÉVALUATIONS — espace élève
|--------------------------------------------------------------------------
| L'élève consulte UNIQUEMENT ses propres évaluations de cours
| (filtrage par eleve_id dans EvaluationsController).
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:eleve'])
    ->prefix('evaluations')
    ->name('evaluations.')
    ->controller(EvaluationsController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
    });

Route::prefix('cahiers-textes')
    ->name('cahiers-textes.pdf.')
    ->middleware('auth')
    ->controller(CahierTextePdfController::class)
    ->group(function () {

        // =========================
        // PDF HISTORIQUE PAR ÉLÈVE
        // =========================
        Route::get('/eleves/{eleve}/pdf', 'create')->name('create');
        Route::post('/eleves/{eleve}/pdf', 'store')->name('store');

        // =========================
        // PDF CAHIER UNIQUE
        // =========================
        Route::get('/{cahier}/pdf', 'download')->name('download');
    });

Route::prefix('documents-administratifs')
    ->name('documents.')
    ->middleware('auth')
    ->controller(DocumentController::class)
    ->group(function () {

        Route::get('/', 'index')->name('index');

        Route::get('/cahiers-textes', 'cahiers')->name('cahiers');

        Route::get('/rapports', 'rapports')->name('rapports');
    });

/*
|--------------------------------------------------------------------------
| OBJECTIFS PÉDAGOGIQUES
|--------------------------------------------------------------------------
*/

Route::prefix('objectifs-pedagogiques')
    ->name('objectifs-pedagogiques.')
    ->middleware('auth')
    ->controller(ObjectifPedagogiqueController::class)
    ->group(function () {

        Route::get('/', 'index')
            ->name('index')
            ->can('viewAny', ObjectifPedagogique::class);

        Route::get('/create', 'create')
            ->name('create')
            ->can('create', ObjectifPedagogique::class);

        Route::post('/', 'store')
            ->name('store')
            ->can('create', ObjectifPedagogique::class);

        Route::get('/{objectifPedagogique}', 'show')
            ->name('show')
            ->can('view', 'objectifPedagogique');

        Route::get('/{objectifPedagogique}/edit', 'edit')
            ->name('edit')
            ->can('update', 'objectifPedagogique');

        Route::put('/{objectifPedagogique}', 'update')
            ->name('update')
            ->can('update', 'objectifPedagogique');

        Route::delete('/{objectifPedagogique}', 'destroy')
            ->name('destroy')
            ->can('delete', 'objectifPedagogique');
    });

/*
|--------------------------------------------------------------------------
| RAPPORTS MENSUELS ENSEIGNANTS
|--------------------------------------------------------------------------
*/

Route::middleware('auth')
    ->prefix('rapports-mensuels')
    ->name('rapports-mensuels.')
    ->controller(RapportMensuelWebController::class)
    ->group(function () {

        Route::get('/', 'index')
            ->name('index')
            ->can('viewAny', RapportMensuelEnseignant::class);

        Route::post('/preview', 'preview')
            ->name('preview');

        Route::get('/{contrat}/create', 'create')
            ->name('create')
            ->can('create', RapportMensuelEnseignant::class);

        Route::post('/{contrat}', 'store')
            ->name('store')
            ->can('create', RapportMensuelEnseignant::class);

        Route::get('/{rapport}', 'show')
            ->name('show')
            ->can('view', 'rapport');

        Route::get('/{rapport}/edit', 'edit')
            ->name('edit')
            ->can('update', 'rapport');

        Route::put('/{rapport}', 'update')
            ->name('update')
            ->can('update', 'rapport');

        Route::delete('/{rapport}', 'destroy')
            ->name('destroy')
            ->can('delete', 'rapport');

        Route::post('/{rapport}/valider', 'valider')
            ->name('validate')
            ->middleware('role:admin_cabinet');

        Route::post('/{rapport}/rejeter', 'reject')
            ->name('reject')
            ->middleware('role:admin_cabinet');
    });

/*
|--------------------------------------------------------------------------
| PDF RAPPORTS MENSUELS
|--------------------------------------------------------------------------
*/

Route::middleware('auth')
    ->prefix('rapports-mensuels/{rapport}/pdf')
    ->name('rapports-mensuels.pdf.')
    ->controller(RapportMensuelPdfController::class)
    ->group(function () {

        Route::get('/', 'stream')
            ->name('stream')
            ->can('view', 'rapport');

        Route::get('/download', 'download')
            ->name('download')
            ->can('view', 'rapport');

        Route::post('/save', 'save')
            ->name('save')
            ->can('view', 'rapport');
    });