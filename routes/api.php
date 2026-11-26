<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\DebtController;
use App\Http\Controllers\Api\V1\ShiftController;
use App\Http\Controllers\Api\V1\MenuController;
use App\Http\Controllers\Api\PublicMenuController;
use App\Http\Controllers\Api\V1\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');
    Route::post('login/exchange', [AuthController::class, 'exchange'])
        ->middleware('throttle:10,1');

    Route::middleware(['auth:sanctum', 'role:owner,cashier'])->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        // Semua crew yang login boleh melihat
        Route::get('menus', [MenuController::class, 'index']);
        Route::get('categories', [CategoryController::class, 'index']);

        Route::get('transactions', [TransactionController::class, 'index']);
        Route::post('transactions', [TransactionController::class, 'store']);
        Route::get('transactions/{transaction}', [TransactionController::class, 'show']);
        Route::get('expenses', [ExpenseController::class, 'index']);
        Route::post('expenses', [ExpenseController::class, 'store']);
        Route::get('expenses/{expense}/receipt', [ExpenseController::class, 'receipt']);
        Route::get('debts', [DebtController::class, 'index']);
        Route::get('debts/{transaction}/payments', [DebtController::class, 'payments']);
        Route::post('debts/{transaction}/payments', [DebtController::class, 'pay']);
        Route::get('shifts/current', [ShiftController::class, 'current']);
        Route::post('shifts', [ShiftController::class, 'open']);
        Route::post('shifts/{shift}/close', [ShiftController::class, 'close']);

        // Hanya owner
        Route::middleware('role:owner')->group(function () {
            Route::post('menus', [MenuController::class, 'store']);
            Route::put('menus/{menu}', [MenuController::class, 'update']);
            Route::put('expenses/{expense}', [ExpenseController::class, 'update']);
            Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy']);
            Route::post('categories', [CategoryController::class, 'store']);
            Route::post('transactions/{transaction}/void', [TransactionController::class, 'void']);

            // Route::post('categories', [CategoryController::class, 'store']);
        });
    });
});

Route::get('public/menus', [PublicMenuController::class, 'index'])
    ->middleware('throttle:60,1');

            // Route::post('categories', [CategoryController::class, 'store']);
            // Route::put('categories/{category}', [CategoryController::class, 'update']);
            // Route::delete('categories/{category}', [CategoryController::class, 'destroy']);
