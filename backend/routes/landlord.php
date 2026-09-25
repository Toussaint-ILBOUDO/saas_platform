<?php

use App\Modules\Landlord\Controllers\AuthenticatedSessionController;
use App\Modules\Landlord\Controllers\CabinetController;
use App\Modules\Landlord\Controllers\DashboardController;
use App\Modules\Landlord\Controllers\JournalController;
use App\Modules\Landlord\Controllers\SectionPlaceholderController;
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

        // Sections (placeholders — implémentation réelle en P7)
        Route::get('facturation', SectionPlaceholderController::class)->defaults('section', 'facturation')->name('facturation.index');
    });
});