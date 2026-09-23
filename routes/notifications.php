<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Systeme\Http\Controllers\NotificationController;

Route::prefix('notifications')
    ->name('notifications.')
    ->middleware('auth')
    ->controller(NotificationController::class)
    ->group(function () {

        Route::get('/', 'index')
            ->name('index');

        Route::post('/read-all', 'markAllAsRead')
            ->name('markAllAsRead');

        Route::get('/unread-count', 'unreadCount')
            ->name('unreadCount');

        Route::post('/{notification}/read', 'markAsRead')
            ->name('markAsRead');

        Route::delete('/{notification}', 'destroy')
            ->name('destroy');

        Route::get('/{notification}', 'show')
            ->name('show');

    });
