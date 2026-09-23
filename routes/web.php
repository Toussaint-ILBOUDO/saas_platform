<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Public\Controllers\HomeController;
use App\Modules\Pedagogie\Http\Controllers\DemandeCoursController;

Route::get('/', [HomeController::class, 'index']);

Route::get(
    '/demander-un-cours',
    [DemandeCoursController::class, 'create']
)->name('demande-cours.create');

Route::post(
    '/demander-un-cours',
    [DemandeCoursController::class, 'store']
)->name('demande-cours.store');