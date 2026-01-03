<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// --- LOGIN SAYFASI (guest) ---
Route::middleware('guest')->group(function () {
    // login route ADI => login   (Laravel bunu bekliyor)
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])
        ->name('login');

    Route::post('/login', [AdminAuthController::class, 'login'])
        ->name('login.submit');
});

// --- ADMIN PANEL (auth + admin) ---
Route::middleware(['auth', 'admin'])->group(function () {
    // ROOT = ADMIN DASHBOARD
    Route::get('/', [DashboardController::class, 'index'])
        ->name('admin.dashboard');

    Route::post('/logout', [AdminAuthController::class, 'logout'])
        ->name('admin.logout');
});
