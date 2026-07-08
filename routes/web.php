<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ProjectController;
use App\Http\Controllers\Auth\LoginController;

/*
|--------------------------------------------------------------------------
| Web Routes - Content Generator Dashboard
|--------------------------------------------------------------------------
|
| Frontend Routes untuk Single Page Application Dashboard
| Menggunakan Vue.js/React SPA dengan API Backend
|
*/

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
});

Route::get('/landing', function () {
    return view('landing');
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATION ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/register', [LoginController::class, 'showRegisterForm']);
    Route::post('/register', [LoginController::class, 'register']);
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATED DASHBOARD ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    
    // Dashboard Home
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/overview', [DashboardController::class, 'overview']);
    
    /*
    |--------------------------------------------------------------------------
    | WORKFLOW 1: TITLE IDEA GENERATOR
    |--------------------------------------------------------------------------
    */
    Route::prefix('title-generator')->group(function () {
        Route::get('/', [DashboardController::class, 'titleGenerator'])->name('title-generator');
        Route::get('/new', [DashboardController::class, 'newTitleProject']);
        Route::get('/{slug}', [DashboardController::class, 'titleProjectDetail']);
    });
    
    /*
    |--------------------------------------------------------------------------
    | WORKFLOW 2: PROMPT ENGINEERING
    |--------------------------------------------------------------------------
    */
    Route::prefix('prompt-engineering')->group(function () {
        Route::get('/', [DashboardController::class, 'promptEngineering'])->name('prompt-engineering');
        Route::get('/{projectId}', [DashboardController::class, 'promptEngineeringDetail']);
    });
    
    /*
    |--------------------------------------------------------------------------
    | WORKFLOW 3: THUMBNAIL STUDIO
    |--------------------------------------------------------------------------
    */
    Route::prefix('thumbnail-studio')->group(function () {
        Route::get('/', [DashboardController::class, 'thumbnailStudio'])->name('thumbnail-studio');
        Route::get('/{projectId}', [DashboardController::class, 'thumbnailStudioDetail']);
        Route::get('/{projectId}/editor/{thumbnailId}', [DashboardController::class, 'thumbnailEditor']);
    });
    
    /*
    |--------------------------------------------------------------------------
    | WORKFLOW 4: SCRIPT WRITER
    |--------------------------------------------------------------------------
    */
    Route::prefix('script-writer')->group(function () {
        Route::get('/', [DashboardController::class, 'scriptWriter'])->name('script-writer');
        Route::get('/{projectId}', [DashboardController::class, 'scriptWriterDetail']);
        Route::get('/{projectId}/scenes', [DashboardController::class, 'scriptScenes']);
    });
    
    /*
    |--------------------------------------------------------------------------
    | WORKFLOW 5: SEO OPTIMIZER
    |--------------------------------------------------------------------------
    */
    Route::prefix('seo-optimizer')->group(function () {
        Route::get('/', [DashboardController::class, 'seoOptimizer'])->name('seo-optimizer');
        Route::get('/{projectId}', [DashboardController::class, 'seoOptimizerDetail']);
    });
    
    /*
    |--------------------------------------------------------------------------
    | PROJECT MANAGEMENT
    |--------------------------------------------------------------------------
    */
    Route::prefix('projects')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('projects');
        Route::get('/create', [ProjectController::class, 'create']);
        Route::post('/', [ProjectController::class, 'store']);
        Route::get('/{slug}', [ProjectController::class, 'show']);
        Route::get('/{slug}/edit', [ProjectController::class, 'edit']);
        Route::put('/{slug}', [ProjectController::class, 'update']);
        Route::delete('/{slug}', [ProjectController::class, 'destroy']);
        Route::post('/{slug}/archive', [ProjectController::class, 'archive']);
        Route::post('/{slug}/duplicate', [ProjectController::class, 'duplicate']);
    });
    
    /*
    |--------------------------------------------------------------------------
    | SETTINGS
    |--------------------------------------------------------------------------
    */
    Route::prefix('settings')->group(function () {
        Route::get('/profile', [DashboardController::class, 'profileSettings']);
        Route::put('/profile', [DashboardController::class, 'updateProfile']);
        Route::get('/api-keys', [DashboardController::class, 'apiKeysSettings']);
        Route::post('/api-keys', [DashboardController::class, 'saveApiKeys']);
        Route::get('/preferences', [DashboardController::class, 'preferencesSettings']);
        Route::put('/preferences', [DashboardController::class, 'updatePreferences']);
    });
    
    /*
    |--------------------------------------------------------------------------
    | ANALYTICS
    |--------------------------------------------------------------------------
    */
    Route::prefix('analytics')->group(function () {
        Route::get('/', [DashboardController::class, 'analytics'])->name('analytics');
        Route::get('/usage', [DashboardController::class, 'usageAnalytics']);
        Route::get('/performance', [DashboardController::class, 'performanceAnalytics']);
    });
    
    /*
    |--------------------------------------------------------------------------
    | TEMPLATES LIBRARY
    |--------------------------------------------------------------------------
    */
    Route::prefix('templates')->group(function () {
        Route::get('/', [DashboardController::class, 'templates']);
        Route::get('/{templateId}', [DashboardController::class, 'templateDetail']);
    });
    
    /*
    |--------------------------------------------------------------------------
    | HISTORY & EXPORT
    |--------------------------------------------------------------------------
    */
    Route::prefix('history')->group(function () {
        Route::get('/', [DashboardController::class, 'history']);
        Route::get('/exports', [DashboardController::class, 'exports']);
    });
});

/*
|--------------------------------------------------------------------------
| SPA FALLBACK - React/Vue SPA Routes
|--------------------------------------------------------------------------
*/
Route::get('/app/{any}', function () {
    return view('dashboard.app');
})->where('any', '.*')->middleware('auth');

Route::get('/{any}', function () {
    return view('dashboard.app');
})->where('any', '.*');
