<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Auth\Http\Controllers\AuthController;
use App\Modules\Auth\Http\Controllers\DashboardController;
use App\Modules\Users\Http\Controllers\ParentController;
use App\Modules\Users\Http\Controllers\EleveController;
use App\Modules\Users\Http\Controllers\EnseignantController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/select-role', [AuthController::class, 'selectRolePage'])
        ->name('role.select');

    Route::post('/select-role', [AuthController::class, 'setRole'])
        ->name('role.set');
});

Route::middleware(['auth'])
    ->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

    });

/*
|--------------------------------------------------------------------------
| CRUD Parents / Élèves / Enseignants — réservé à l'administration
|--------------------------------------------------------------------------
| Sécurité : ces resources manipulent des données personnelles et des
| comptes (création, modification, activation, mot de passe).
| Elles sont donc restreintes aux rôles admin/super-admin.
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin|super-admin'])->group(function () {

    Route::resource(
        'parents',
        ParentController::class
    )->except(['destroy']);

    Route::controller(EleveController::class)
        ->prefix('eleves')
        ->name('eleves.')
        ->group(function () {

            // =====================
            // ACTION MÉTIER
            // =====================
            Route::get('{eleve}/account', 'accountForm')->name('account.form');
            Route::post('{eleve}/account', 'activateAccount')->name('account.activate');
        });

    Route::resource('eleves', EleveController::class)
        ->parameters(['eleves' => 'eleve'])
        ->except(['destroy']);

    Route::resource('enseignants', EnseignantController::class)
        ->parameters(['enseignants' => 'enseignant'])
        ->except(['destroy']);

});

/*
|--------------------------------------------------------------------------
| FICHE ÉLÈVE — accès propriétaire / parent / enseignant
|--------------------------------------------------------------------------
| Tout utilisateur authentifié peut consulter la fiche d'un élève SI la
| policy ElevePolicy::view l'y autorise (l'élève lui-même, son parent,
| un enseignant affecté). Le CRUD admin ci-dessus reste réservé.
|--------------------------------------------------------------------------
*/

Route::middleware('auth')
    ->get('/eleves/{eleve}/fiche', [EleveController::class, 'show'])
    ->name('eleves.fiche')
    ->can('view', 'eleve');

/*
|--------------------------------------------------------------------------
| MES ENFANTS — espace parent
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:parent'])
    ->get('/mes-enfants', [EleveController::class, 'mesEnfants'])
    ->name('mes-enfants');

/*
|--------------------------------------------------------------------------
| MON PROFIL
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->prefix('mon-profil')->name('profil.')->group(function () {
    Route::get('/', [App\Modules\Auth\Http\Controllers\ProfilController::class, 'edit'])->name('edit');
    Route::put('/', [App\Modules\Auth\Http\Controllers\ProfilController::class, 'update'])->name('update');
});