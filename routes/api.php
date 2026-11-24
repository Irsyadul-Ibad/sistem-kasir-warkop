<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\MenuController;
use App\Http\Controllers\Api\V1\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\V1\AuthController::class, 'login']);
    Route::middleware('throttle:5,1');
    
Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        //semua crew yang login boleh melihat
        Route::get('menus', [MenuController::class, 'index']);
        Route::get('categories', [CategoryController::class, 'index']);
        Route::post('transactions', [TransactionController::class, 'store']);
        Route::get('transactions/{transaction}', [TransactionController::class, 'show']);
        Route::get('transactions', [TransactionController::class, 'show']);

        //hanya owner
Route::middleware('role:owner')->group(function () {
    Route::post('menus', [MenuController::class, 'store']);
    Route::put('menus/{menu}', [MenuController::class, 'update']);
    Route::delete('menus/{menu}', [MenuController::class, 'destroy']);

            // Route::post('categories', [CategoryController::class, 'store']);
            // Route::put('categories/{category}', [CategoryController::class, 'update']);
            // Route::delete('categories/{category}', [CategoryController::class, 'destroy']);
           
        });
    });
});