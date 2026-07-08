<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ContentGeneratorController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\ThumbnailController;
use App\Http\Controllers\Api\V1\ScriptController;
use App\Http\Controllers\Api\V1\SeoController;

/*
|--------------------------------------------------------------------------
| API Routes - Content Generator All-in-One
|--------------------------------------------------------------------------
|
| Route Architecture untuk Dashboard Workflow:
| 1. Title Idea Generator
| 2. Auto-Prompt Engineering  
| 3. Thumbnail Generator
| 4. Video Script Creation
| 5. Final Video Title & SEO
|
*/

// API Version 1
Route::prefix('v1')->group(function () {
    
    // ============================================
    // PROJECT MANAGEMENT
    // ============================================
    Route::prefix('projects')->group(function () {
        Route::get('/', [ProjectController::class, 'index']);
        Route::post('/', [ProjectController::class, 'store']);
        Route::get('/{uuid}', [ProjectController::class, 'show']);
        Route::put('/{uuid}', [ProjectController::class, 'update']);
        Route::delete('/{uuid}', [ProjectController::class, 'destroy']);
        Route::post('/{uuid}/duplicate', [ProjectController::class, 'duplicate']);
        Route::get('/{uuid}/workflow-status', [ProjectController::class, 'workflowStatus']);
    });

    // ============================================
    // WORKFLOW 1: TITLE IDEA GENERATOR
    // ============================================
    Route::prefix('title-generator')->group(function () {
        Route::post('/generate', [ContentGeneratorController::class, 'generateTitleIdeas']);
        Route::post('/generate-variations', [ContentGeneratorController::class, 'generateTitleVariations']);
        Route::post('/analyze-keywords', [ContentGeneratorController::class, 'analyzeKeywords']);
        Route::get('/{projectId}/history', [ContentGeneratorController::class, 'getTitleHistory']);
    });

    // ============================================
    // WORKFLOW 2: AUTO-PROMPT ENGINEERING
    // ============================================
    Route::prefix('prompt-engineering')->group(function () {
        Route::post('/generate-text-prompt', [ContentGeneratorController::class, 'generateTextPrompt']);
        Route::post('/generate-visual-prompt', [ContentGeneratorController::class, 'generateVisualPrompt']);
        Route::post('/generate-thumbnail-prompt', [ContentGeneratorController::class, 'generateThumbnailPrompt']);
        Route::post('/generate-storyboard-prompt', [ContentGeneratorController::class, 'generateStoryboardPrompt']);
        Route::post('/optimize-prompt', [ContentGeneratorController::class, 'optimizePrompt']);
        Route::get('/{projectId}/prompts', [ContentGeneratorController::class, 'getProjectPrompts']);
        Route::get('/{promptId}', [ContentGeneratorController::class, 'getPromptDetails']);
    });

    // ============================================
    // WORKFLOW 3: THUMBNAIL GENERATOR
    // ============================================
    Route::prefix('thumbnails')->group(function () {
        Route::post('/generate', [ThumbnailController::class, 'generateThumbnail']);
        Route::post('/generate-batch', [ThumbnailController::class, 'generateBatch']);
        Route::get('/{thumbnailId}', [ThumbnailController::class, 'show']);
        Route::put('/{thumbnailId}', [ThumbnailController::class, 'update']);
        Route::delete('/{thumbnailId}', [ThumbnailController::class, 'destroy']);
        Route::post('/{thumbnailId}/regenerate', [ThumbnailController::class, 'regenerate']);
        Route::post('/{thumbnailId}/select', [ThumbnailController::class, 'selectAsPrimary']);
        Route::get('/{projectId}/gallery', [ThumbnailController::class, 'getProjectThumbnails']);
        Route::post('/{thumbnailId}/download', [ThumbnailController::class, 'downloadThumbnail']);
    });

    // ============================================
    // WORKFLOW 4: VIDEO SCRIPT CREATION
    // ============================================
    Route::prefix('scripts')->group(function () {
        Route::post('/generate', [ScriptController::class, 'generateScript']);
        Route::post('/generate-from-prompt', [ScriptController::class, 'generateFromPrompt']);
        Route::get('/{scriptId}', [ScriptController::class, 'show']);
        Route::put('/{scriptId}', [ScriptController::class, 'update']);
        Route::delete('/{scriptId}', [ScriptController::class, 'destroy']);
        
        // Script Scenes Management
        Route::get('/{scriptId}/scenes', [ScriptController::class, 'getScenes']);
        Route::post('/{scriptId}/scenes', [ScriptController::class, 'addScene']);
        Route::put('/scenes/{sceneId}', [ScriptController::class, 'updateScene']);
        Route::delete('/scenes/{sceneId}', [ScriptController::class, 'deleteScene']);
        Route::post('/scenes/{sceneId}/generate-thumbnail', [ScriptController::class, 'generateSceneThumbnail']);
        
        // Script Templates
        Route::get('/templates', [ScriptController::class, 'getTemplates']);
        Route::post('/templates', [ScriptController::class, 'saveAsTemplate']);
        
        // Export
        Route::get('/{scriptId}/export', [ScriptController::class, 'exportScript']);
    });

    // ============================================
    // WORKFLOW 5: FINAL TITLE & SEO OPTIMIZATION
    // ============================================
    Route::prefix('seo')->group(function () {
        Route::post('/optimize', [SeoController::class, 'optimizeContent']);
        Route::post('/generate-final-title', [SeoController::class, 'generateFinalTitle']);
        Route::post('/generate-tags', [SeoController::class, 'generateTags']);
        Route::post('/analyze-competitors', [SeoController::class, 'analyzeCompetitors']);
        Route::post('/predict-performance', [SeoController::class, 'predictPerformance']);
        Route::get('/{projectId}', [SeoController::class, 'getSeoData']);
        Route::put('/{seoId}', [SeoController::class, 'updateSeo']);
        Route::post('/apply-seo/{projectId}', [SeoController::class, 'applySeoToProject']);
    });

    // ============================================
    // AI PROVIDER MANAGEMENT
    // ============================================
    Route::prefix('ai-providers')->group(function () {
        Route::get('/status', [ContentGeneratorController::class, 'getProviderStatus']);
        Route::post('/gemini/test', [ContentGeneratorController::class, 'testGeminiConnection']);
        Route::get('/usage-stats', [ContentGeneratorController::class, 'getUsageStats']);
    });

    // ============================================
    // WORKFLOW AUTOMATION
    // ============================================
    Route::prefix('workflow')->group(function () {
        Route::post('/start', [ContentGeneratorController::class, 'startWorkflow']);
        Route::post('/execute-step', [ContentGeneratorController::class, 'executeWorkflowStep']);
        Route::get('/{projectId}/status', [ContentGeneratorController::class, 'getWorkflowStatus']);
        Route::post('/{projectId}/cancel', [ContentGeneratorController::class, 'cancelWorkflow']);
    });
});

// Health Check
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0'
    ]);
});
