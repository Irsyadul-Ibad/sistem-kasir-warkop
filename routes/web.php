<?php

use App\Http\Controllers\OwnerMenuController;
use App\Http\Controllers\OwnerExpenseController;
use App\Http\Controllers\OwnerDebtController;
use App\Http\Controllers\OwnerUserController;
use App\Http\Controllers\OwnerWebController;
use App\Http\Controllers\CrewLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $user = auth()->user();
    if ($user?->is_active && $user->role === 'owner') {
        return redirect()->route('owner.dashboard');
    }
    if ($user?->is_active && $user->role === 'cashier') {
        return redirect(config('app.frontend_url'));
    }

    return redirect()->route('crew.login');
});

Route::get('/login', [CrewLoginController::class, 'show'])->name('crew.login');
Route::post('/login', [CrewLoginController::class, 'login'])->middleware('throttle:5,1')->name('crew.authenticate');
$serveFrontend = static function () {
    return response(
        file_get_contents(public_path('app/index.html')),
        200,
        ['Content-Type' => 'text/html; charset=UTF-8'],
    );
};

Route::prefix('owner')->name('owner.')->group(function () {
    Route::get('/login', fn () => redirect()->route('crew.login'))->name('login');

    Route::middleware(['auth', 'role:owner'])->group(function () {
        Route::get('/', [OwnerWebController::class, 'dashboard'])->name('dashboard');
        Route::get('/dashboard', [OwnerWebController::class, 'dashboard'])->name('dashboard.view');
        Route::get('/transactions', [OwnerWebController::class, 'transactions'])->name('transactions');
        Route::get('/expenses', [OwnerExpenseController::class, 'index'])->name('expenses');
        Route::post('/expenses', [OwnerExpenseController::class, 'store'])->name('expenses.store');
        Route::get('/expenses/{expense}/receipt', [OwnerExpenseController::class, 'receipt'])->name('expenses.receipt');
        Route::get('/expenses/{expense}/edit', [OwnerExpenseController::class, 'edit'])->name('expenses.edit');
        Route::put('/expenses/{expense}', [OwnerExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('/expenses/{expense}', [OwnerExpenseController::class, 'destroy'])->name('expenses.destroy');
        Route::get('/debts', [OwnerDebtController::class, 'index'])->name('debts');
        Route::post('/debts/{transaction}/payments', [OwnerDebtController::class, 'pay'])->name('debts.pay');
        Route::get('/users', [OwnerUserController::class, 'index'])->name('users');
        Route::get('/users/create', [OwnerUserController::class, 'create'])->name('users.create');
        Route::post('/users', [OwnerUserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [OwnerUserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [OwnerUserController::class, 'update'])->name('users.update');
        Route::post('/logout', [OwnerWebController::class, 'logout'])->name('logout');

        Route::get('/menus', [OwnerMenuController::class, 'index'])->name('menus.index');
        Route::get('/menus/create', [OwnerMenuController::class, 'create'])->name('menus.create');
        Route::post('/menus', [OwnerMenuController::class, 'store'])->name('menus.store');
        Route::get('/menus/{menu}/edit', [OwnerMenuController::class, 'edit'])->name('menus.edit');
        Route::put('/menus/{menu}', [OwnerMenuController::class, 'update'])->name('menus.update');
    });
});

// The PHP dev server treats the public /app directory as the base URL on deep links.
Route::get('/{path}', $serveFrontend)
    ->where('path', '(?!api(?:/|$)).*')
    ->name('frontend.app');

