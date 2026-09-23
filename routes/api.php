<?php

// use Illuminate\Support\Facades\Route;
// use App\Modules\Pedagogie\Http\Controllers\ContratCoursController;
// use App\Modules\Finance\Http\Controllers\PeriodeComptableController;
// use App\Modules\Pedagogie\Http\Controllers\CahierTexteController;
// use App\Modules\Pedagogie\Http\Controllers\RapportMensuelController;
// use App\Modules\Finance\Http\Controllers\FactureController;
// use App\Modules\Finance\Http\Controllers\PaiementEnseignantController;
// use App\Modules\Systeme\Http\Controllers\NotificationController;

// Route::apiResource('contrats', ContratCoursController::class)
//     ->only(['index', 'show', 'store']);


// Route::prefix('periodes')->group(function () {
//     Route::get('/', [PeriodeComptableController::class, 'index']);
//     Route::post('/', [PeriodeComptableController::class, 'store']);
//     Route::get('/{periode}', [PeriodeComptableController::class, 'show']);
//     Route::put('/{periode}', [PeriodeComptableController::class, 'update']);
//     Route::delete('/{periode}', [PeriodeComptableController::class, 'destroy']);

//     Route::post('/{periode}/close', [PeriodeComptableController::class, 'close']);
// });


// Route::prefix('cahier-texte')->group(function () {
//     Route::post('/', [CahierTexteController::class, 'store']);
//     Route::get('/', [CahierTexteController::class, 'index']);
// });

// Route::middleware('auth:sanctum')->group(function() {
//     Route::post('/rapports-mensuels', [RapportMensuelController::class, 'store']);
// });

// Route::prefix('factures')
//     ->group(function () {
//         Route::get('/', [FactureController::class, 'index']);
//         Route::get('/{facture}', [FactureController::class, 'show']);
//         Route::post('/generer', [FactureController::class, 'generate']);
//         Route::patch('/{facture}/payer', [FactureController::class, 'payer']);
//         Route::delete('/{facture}', [FactureController::class, 'destroy']);
//     });

// Route::prefix('paiements-enseignants')
//     ->group(function () {

//         Route::post(
//             '/',
//             [PaiementEnseignantController::class, 'store']
//         );
//     });    


//     // ROutes pour les notifications
//     Route::middleware('auth:sanctum')
//         ->prefix('notifications')
//         ->group(function () {

//             Route::get(
//                 '/',
//                 [NotificationController::class, 'index']
//             );
//             Route::post(
//                 '/',
//                 [NotificationController::class, 'store']
//             );
//             Route::get(
//                 '/{notification}',
//                 [NotificationController::class, 'show']
//             );
//             Route::patch(
//                 '/{notification}/read',
//                 [NotificationController::class, 'markAsRead']
//             );
//             Route::patch(
//                 '/read-all',
//                 [NotificationController::class, 'markAllAsRead']
//             );
//             Route::delete(
//                 '/{notification}',
//                 [NotificationController::class, 'destroy']
//             );

//             Route::get('/unread-count', [
//                 NotificationController::class,
//                 'unreadCount'
//             ]);

//             Route::patch(
//                 '/{notification}/read',
//                 [NotificationController::class, 'markAsRead']
//             );

//             Route::patch(
//                 '/read-all',
//                 [NotificationController::class, 'markAllAsRead']
//             );
//         });




        