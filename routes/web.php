<?php

use App\Http\Controllers\Admin\ArticleAdminController;
use App\Http\Controllers\Admin\CatalogItemController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PublicArticleController;
use App\Http\Controllers\RegionalController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

// --- SEO ---------------------------------------------------------------
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

// --- Auth --------------------------------------------------------------
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1')->name('login.attempt');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// --- Public -------------------------------------------------------------
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::prefix('artikel')->name('articles.')->group(function (): void {
    Route::get('/', [PublicArticleController::class, 'index'])->name('index');
    Route::get('/{article:slug}', [PublicArticleController::class, 'show'])->name('show');
});

Route::prefix('wilayah')->name('regional.')->group(function (): void {
    Route::get('/', [RegionalController::class, 'cities'])->name('cities');
    Route::get('/{city:slug}', [RegionalController::class, 'city'])->name('city');

    // City-contextual catalog: MUST come before any {kecamatan:slug} catch-all,
    // otherwise '/wilayah/{city}/pelatihan' matches as a kecamatan slug (404).
    Route::get('/{city:slug}/{category}', [CatalogController::class, 'cityIndex'])
        ->whereIn('category', ['pelatihan', 'jasa'])
        ->name('city.catalog.index');
    Route::get('/{city:slug}/{category}/{item:slug}', [CatalogController::class, 'cityShow'])
        ->whereIn('category', ['pelatihan', 'jasa'])
        ->name('city.catalog.show');

    // Kecamatan-contextual catalog: kecamatan slug is only unique per city, so
    // the controller resolves it scoped to the bound city (never a global bind).
    Route::get('/{city:slug}/{kecamatan:slug}/{category}', [CatalogController::class, 'kecamatanIndex'])
        ->whereIn('category', ['pelatihan', 'jasa'])
        ->name('kecamatan.catalog.index');
    Route::get('/{city:slug}/{kecamatan:slug}/{category}/{item:slug}', [CatalogController::class, 'kecamatanShow'])
        ->whereIn('category', ['pelatihan', 'jasa'])
        ->name('kecamatan.catalog.show');

    // Kecamatan landing page — declared last so the whereIn-constrained catalog
    // routes above win for pelatihan/jasa segments.
    Route::get('/{city:slug}/{kecamatan:slug}', [RegionalController::class, 'kecamatan'])->name('kecamatan');
});

// pelatihan + jasa menugenerate two friendly categories.
Route::get('/{category}', [CatalogController::class, 'index'])
    ->whereIn('category', ['pelatihan', 'jasa'])
    ->name('catalog.index');
Route::get('/{category}/{item:slug}', [CatalogController::class, 'show'])
    ->whereIn('category', ['pelatihan', 'jasa'])
    ->name('catalog.show');

// City-specific catalog routes (wilayah/{city}/{category} and wilayah/{city}/{category}/{item})
Route::prefix('wilayah/{city:slug}')->name('regional.')->group(function (): void {
    Route::get('/{category}', [CatalogController::class, 'cityIndex'])
        ->whereIn('category', ['pelatihan', 'jasa'])
        ->name('city.catalog.index');
    Route::get('/{category}/{item:slug}', [CatalogController::class, 'cityShow'])
        ->whereIn('category', ['pelatihan', 'jasa'])
        ->name('city.catalog.show');
});

// --- Admin -----------------------------------------------------------------
Route::prefix('admin')->middleware(['auth', 'admin'])->name('admin.')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Draft-only AI generation endpoint: must come before the resource so it
    // is not shadowed by {article}.
    Route::post('articles/generate-preview', [ArticleAdminController::class, 'generatePreview'])
        ->name('articles.generate-preview');
    Route::post('articles/ai-generate', [ArticleAdminController::class, 'aiGenerate'])
        ->name('articles.ai-generate');

    // Image upload for Jodit editor
    Route::post('articles/upload-image', [ArticleAdminController::class, 'uploadImage'])
        ->name('articles.upload-image');
    Route::post('upload/image', [ArticleAdminController::class, 'uploadImage'])
        ->name('upload.image');

    Route::get('cities/{city:slug}/kecamatans', [ArticleAdminController::class, 'kecamatans'])
        ->name('cities.kecamatans');

    Route::resource('articles', ArticleAdminController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
        ->parameters(['articles' => 'article:id']);

    Route::resource('catalog', CatalogItemController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
});
