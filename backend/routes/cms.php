<?php

use App\Modules\Communication\Http\Controllers\FaqQuestionController;
use App\Modules\Communication\Http\Controllers\FaqSectionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes Communication — FAQ (CMS)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin|super-admin'])
    ->prefix('admin/cms/faq')
    ->name('admin.faq.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | SECTIONS FAQ
        |--------------------------------------------------------------------------
        */

        Route::get('/', [FaqSectionController::class, 'index'])
            ->name('sections.index');

        Route::get('/sections/creer', [FaqSectionController::class, 'create'])
            ->name('sections.create');
        Route::post('/sections', [FaqSectionController::class, 'store'])
            ->name('sections.store');

        Route::get('/sections/{section}/modifier', [FaqSectionController::class, 'edit'])
            ->name('sections.edit');
        Route::put('/sections/{section}', [FaqSectionController::class, 'update'])
            ->name('sections.update');
        Route::patch('/sections/{section}/toggle', [FaqSectionController::class, 'toggle'])
            ->name('sections.toggle');
        Route::delete('/sections/{section}', [FaqSectionController::class, 'destroy'])
            ->name('sections.destroy');

        /*
        |--------------------------------------------------------------------------
        | QUESTIONS FAQ (rattachées à une section)
        |--------------------------------------------------------------------------
        */

        Route::get('/sections/{section}/questions', [FaqQuestionController::class, 'index'])
            ->name('questions.index');

        Route::get('/sections/{section}/questions/creer', [FaqQuestionController::class, 'create'])
            ->name('questions.create');
        Route::post('/sections/{section}/questions', [FaqQuestionController::class, 'store'])
            ->name('questions.store');

        Route::get('/sections/{section}/questions/{question}/modifier', [FaqQuestionController::class, 'edit'])
            ->name('questions.edit');
        Route::put('/sections/{section}/questions/{question}', [FaqQuestionController::class, 'update'])
            ->name('questions.update');
        Route::patch('/sections/{section}/questions/{question}/toggle', [FaqQuestionController::class, 'toggle'])
            ->name('questions.toggle');
        Route::delete('/sections/{section}/questions/{question}', [FaqQuestionController::class, 'destroy'])
            ->name('questions.destroy');
    });
