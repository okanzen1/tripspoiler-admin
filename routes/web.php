<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AffiliatePartnerController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\MuseumController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BlogContentController;

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
    Route::resource('affiliate-partners', AffiliatePartnerController::class);
    Route::resource('users', UserController::class);
    Route::resource('faqs', FaqController::class);
    Route::resource('cities', CityController::class);
    Route::resource('museums', MuseumController::class);
    Route::resource('countries', CountryController::class);
    Route::resource('activities', ActivityController::class);
    Route::resource('blogs', BlogController::class);
    Route::get('/blogs/{blog}/contents/create', [BlogContentController::class, 'create'])->name('blogs.content.create');
    Route::post('/blogs/{blog}/contents', [BlogContentController::class, 'store'])->name('blogs.content.store');
    Route::get('/blogs/{blog}/contents/{content}/edit', [BlogContentController::class, 'edit'])->name('blogs.content.edit');
    Route::put('/blogs/{blog}/contents/{content}', [BlogContentController::class, 'update'])->name('blogs.content.update');
    Route::delete('/blogs/{blog}/contents/{content}', [BlogContentController::class, 'destroy'])->name('blogs.content.destroy');
    Route::post('/images/upload', [ImageController::class, 'store'])->name('images.upload');
    Route::post('/images/sort', [ImageController::class, 'sort'])->name('images.sort');
    Route::delete('/images/{image}', [ImageController::class, 'destroy'])->name('images.destroy');
    Route::get('/media/{image}', [ImageController::class, 'show'])->name('images.view');
});
