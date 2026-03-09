<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AffiliatePartnerController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BlogContentController;
use App\Http\Controllers\BlogSubscriberController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PageContentController;
use App\Http\Controllers\CityExperienceCategoryController;
use App\Http\Controllers\DevelopController;
use App\Http\Controllers\TranslatorController;

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
    Route::resource('countries', CountryController::class);
    Route::resource('pages', PageController::class);
    Route::get('/pages/{page}/contents/{city}', [PageContentController::class, 'show'])->name('pages.contents.show');
    Route::post('/pages/{page}/contents', [PageContentController::class, 'storeOrUpdate'])->name('pages.contents.store');

    Route::resource('activities', ActivityController::class);
    Route::patch('/activities/{activity}/toggle-status',[ActivityController::class, 'toggleStatus'])->name('activities.toggle-status');
    Route::post('/admin/translate',[ActivityController::class,'autoTranslate'])->name('admin.translate');
    Route::post('/admin/save-translation',[ActivityController::class,'saveTranslation'])->name('admin.saveTranslation');

    Route::get('page-contents/{pageContent}/experience-categories',[CityExperienceCategoryController::class, 'index']);
    Route::post('page-contents/{pageContent}/experience-categories',[CityExperienceCategoryController::class, 'store']);
    Route::delete('experience-categories/{category}',[CityExperienceCategoryController::class, 'destroy'])->name('experience-categories.destroy');
    Route::patch('experience-categories/{category}/toggle-status',[CityExperienceCategoryController::class, 'toggleStatus']);
    Route::get('experience-categories/{category}/edit',[CityExperienceCategoryController::class, 'edit']);
    Route::put('experience-categories/{category}',[CityExperienceCategoryController::class, 'update'])->name('experience-categories.update');

    Route::resource('blogs', BlogController::class);
    Route::get('/blogs/{blog}/contents/create', [BlogContentController::class, 'create'])->name('blogs.content.create');
    Route::post('/blogs/{blog}/contents', [BlogContentController::class, 'store'])->name('blogs.content.store');
    Route::get('/blogs/{blog}/contents/{content}/edit', [BlogContentController::class, 'edit'])->name('blogs.content.edit');
    Route::put('/blogs/{blog}/contents/{content}', [BlogContentController::class, 'update'])->name('blogs.content.update');
    Route::delete('/blogs/{blog}/contents/{content}', [BlogContentController::class, 'destroy'])->name('blogs.content.destroy');
    Route::resource('blog-subscribers', BlogSubscriberController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::post('/images/upload', [ImageController::class, 'store'])->name('images.upload');
    Route::post('/images/sort', [ImageController::class, 'sort'])->name('images.sort');
    Route::delete('/images/{image}', [ImageController::class, 'destroy'])->name('images.destroy');
    Route::get('/media/{image}', [ImageController::class, 'show'])->name('images.view');

    Route::get('/admin/translators',[TranslatorController::class,'index'])->name('translators.index');
    Route::post('/admin/translators',[TranslatorController::class,'store'])->name('translators.store');
    Route::patch('/admin/translators/{translator}/toggle',[TranslatorController::class,'toggle'])->name('translators.toggle');
    Route::delete('/admin/translators/{translator}',[TranslatorController::class,'destroy'])->name('translators.destroy');
    
    Route::get('/develop', [DevelopController::class, 'index']);
});
