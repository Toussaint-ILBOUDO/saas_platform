<?php

use App\Modules\Landlord\Controllers\AuthenticatedSessionController;
use App\Modules\Landlord\Controllers\DashboardController;
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
    });
});