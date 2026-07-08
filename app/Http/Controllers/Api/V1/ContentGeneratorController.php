<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\GeneratedPrompt;
use App\Models\Thumbnail;
use App\Models\VideoScript;
use App\Models\SeoOptimization;
use App\Services\GeminiAiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class ContentGeneratorController extends Controller
{
    private GeminiAiService $geminiService;
    
    public function __construct(GeminiAiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }
    
    // ============================================
    // WORKFLOW 1: TITLE IDEA GENERATOR
    // ============================================
    
    /**
     * Generate title ideas based on keywords
     */
    public function generateTitleIdeas(Request $request): JsonResponse
    {
        $request->validate([
            'keywords' => 'required|string|max:500',
            'count' => 'nullable|integer|min:1|max:20',
            'platform' => 'nullable|in:youtube,tiktok,instagram,all',
            'project_id' => 'nullable|uuid|exists:projects,id'
        ]);
        
        try {
            DB::beginTransaction();
            
            $keywords = $request->input('keywords');
            $count = $request->input('count', 10);
            $platform = $request->input('platform', 'youtube');
            
            // Update project status if provided
            if ($request->project_id) {
                Project::where('id', $request->project_id)
                    ->update(['status' => 'idea_generating']);
            }
            
            // Generate titles using Gemini
            $result = $this->geminiService->generateTitleIdeas($keywords, $count, $platform);
            
            if (!$result['success']) {
                throw new Exception($result['error'] ?? 'Failed to generate title ideas');
            }
            
            // Parse the JSON response
            $titlesData = $this->parseJsonResponse($result['content']);
            
            // Create prompt record
            $prompt = GeneratedPrompt::create([
                'project_id' => $request->project_id,
                'prompt_type' => 'title_idea',
                'input_keywords' => $keywords,
                'generated_prompt' => "Generate {$count} titles for: {$keywords}",
                'ai_response' => $result['content'],
                'variations' => $titlesData['titles'] ?? [],
                'metadata' => [
                    'platform' => $platform,
                    'count' => $count,
                    'recommended' => $titlesData['primary_recommendation'] ?? 0
                ],
                'status' => 'completed',
                'processing_time_ms' => $result['processing_time_ms'] ?? 0
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Title ideas generated successfully',
                'data' => [
                    'prompt_id' => $prompt->id,
                    'titles' => $titlesData['titles'] ?? [],
                    'recommended_index' => $titlesData['primary_recommendation'] ?? 0,
                    'reasoning' => $titlesData['reasoning'] ?? '',
                    'processing_time_ms' => $result['processing_time_ms'] ?? 0
                ]
            ]);
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Title generation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate title ideas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Generate title variations from selected title
     */
    public function generateTitleVariations(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:200',
            'project_id' => 'nullable|uuid|exists:projects,id'
        ]);
        
        try {
            $title = $request->input('title');
            
            $prompt = <<<PROMPT
Generate 5 variations of this YouTube title while maintaining the core message:

Original Title: {$title}

Create variations that:
1. Change the emotional angle
2. Use a different power word
3. Convert to a question format
4. Use numbers differently
5. Try a completely different angle

Return as JSON:
{
    "variations": [
        {"title": "Variation 1", "style": "emotional|numeric|question|list|other"},
        {"title": "Variation 2", "style": "emotional|numeric|question|list|other"},
        {"title": "Variation 3", "style": "emotional|numeric|question|list|other"},
        {"title": "Variation 4", "style": "emotional|numeric|question|list|other"},
        {"title": "Variation 5", "style": "emotional|numeric|question|list|other"}
    ],
    "best_for": "which variation works best for which audience"
}

JSON output only:
PROMPT;
            
            $result = $this->geminiService->generateContent($prompt, [
                'temperature' => 0.8,
                'max_tokens' => 2048
            ]);
            
            if (!$result['success']) {
                throw new Exception($result['error'] ?? 'Failed to generate variations');
            }
            
            $variations = $this->parseJsonResponse($result['content']);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'original_title' => $title,
                    'variations' => $variations['variations'] ?? [],
                    'best_for' => $variations['best_for'] ?? ''
                ]
            ]);
            
        } catch (Exception $e) {
            Log::error('Title variation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate title variations',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get title generation history for a project
     */
    public function getTitleHistory(string $projectId): JsonResponse
    {
        try {
            $history = GeneratedPrompt::where('project_id', $projectId)
                ->where('prompt_type', 'title_idea')
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $history
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch title history',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // ============================================
    // WORKFLOW 2: AUTO-PROMPT ENGINEERING
    // ============================================
    
    /**
     * Generate text content prompt
     */
    public function generateTextPrompt(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:200',
            'context' => 'nullable|string|max:2000',
            'script_type' => 'nullable|in:tutorial,review,vlog,educational,entertainment,shorts,livestream',
            'project_id' => 'nullable|uuid|exists:projects,id'
        ]);
        
        try {
            $result = $this->geminiService->generateTextPrompt(
                $request->input('title'),
                $request->input('context', ''),
                $request->input('script_type', 'tutorial')
            );
            
            if (!$result['success']) {
                throw new Exception($result['error'] ?? 'Failed to generate text prompt');
            }
            
            $promptData = $this->parseJsonResponse($result['content']);
            
            $prompt = GeneratedPrompt::create([
                'project_id' => $request->project_id,
                'prompt_type' => 'text_content',
                'input_keywords' => $request->input('title'),
                'generated_prompt' => $promptData['main_prompt'] ?? $result['content'],
                'ai_response' => $result['content'],
                'variations' => $promptData,
                'metadata' => [
                    'script_type' => $request->script_type,
                    'structure' => $promptData['content_structure'] ?? null
                ],
                'status' => 'completed',
                'processing_time_ms' => $result['processing_time_ms'] ?? 0
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'prompt_id' => $prompt->id,
                    'main_prompt' => $promptData['main_prompt'] ?? '',
                    'structure' => $promptData['content_structure'] ?? [],
                    'style_guidelines' => $promptData['style_guidelines'] ?? '',
                    'seo_keywords' => $promptData['seo_keywords'] ?? []
                ]
            ]);
            
        } catch (Exception $e) {
            Log::error('Text prompt generation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate text prompt',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Generate visual prompt for thumbnails
     */
    public function generateVisualPrompt(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:200',
            'style' => 'nullable|in:minimalist,bold_text,character_focused,text_overlay,concept_art,photographic',
            'project_id' => 'nullable|uuid|exists:projects,id'
        ]);
        
        try {
            $style = $request->input('style', 'bold_text');
            
            $result = $this->geminiService->generateVisualPrompt(
                $request->input('title'),
                $style
            );
            
            if (!$result['success']) {
                throw new Exception($result['error'] ?? 'Failed to generate visual prompt');
            }
            
            $promptData = $this->parseJsonResponse($result['content']);
            
            $prompt = GeneratedPrompt::create([
                'project_id' => $request->project_id,
                'prompt_type' => 'visual_thumbnail',
                'input_keywords' => $request->input('title'),
                'generated_prompt' => $promptData['image_prompt'] ?? '',
                'ai_response' => $result['content'],
                'variations' => $promptData,
                'metadata' => [
                    'style' => $style,
                    'color_palette' => $promptData['color_palette'] ?? null,
                    'composition' => $promptData['composition'] ?? null
                ],
                'status' => 'completed',
                'processing_time_ms' => $result['processing_time_ms'] ?? 0
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'prompt_id' => $prompt->id,
                    'image_prompt' => $promptData['image_prompt'] ?? '',
                    'style_modifiers' => $promptData['style_modifiers'] ?? [],
                    'color_palette' => $promptData['color_palette'] ?? [],
                    'composition' => $promptData['composition'] ?? [],
                    'mood' => $promptData['mood'] ?? ''
                ]
            ]);
            
        } catch (Exception $e) {
            Log::error('Visual prompt generation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate visual prompt',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Generate thumbnail-specific prompt (alias for visual prompt)
     */
    public function generateThumbnailPrompt(Request $request): JsonResponse
    {
        return $this->generateVisualPrompt($request);
    }
    
    /**
     * Generate storyboard/scene prompts
     */
    public function generateStoryboardPrompt(Request $request): JsonResponse
    {
        $request->validate([
            'script' => 'required|string',
            'scene_count' => 'nullable|integer|min:3|max:20',
            'project_id' => 'nullable|uuid|exists:projects,id'
        ]);
        
        try {
            $result = $this->geminiService->generateStoryboardPrompt(
                $request->input('script'),
                $request->input('scene_count', 6)
            );
            
            if (!$result['success']) {
                throw new Exception($result['error'] ?? 'Failed to generate storyboard');
            }
            
            $storyboardData = $this->parseJsonResponse($result['content']);
            
            $prompt = GeneratedPrompt::create([
                'project_id' => $request->project_id,
                'prompt_type' => 'visual_storyboard',
                'input_keywords' => substr($request->input('script'), 0, 500),
                'generated_prompt' => $result['content'],
                'ai_response' => $result['content'],
                'variations' => $storyboardData['scenes'] ?? [],
                'metadata' => [
                    'scene_count' => count($storyboardData['scenes'] ?? []),
                    'transitions' => $storyboardData['transitions'] ?? [],
                    'total_duration' => $storyboardData['total_estimated_duration'] ?? ''
                ],
                'status' => 'completed',
                'processing_time_ms' => $result['processing_time_ms'] ?? 0
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'prompt_id' => $prompt->id,
                    'scenes' => $storyboardData['scenes'] ?? [],
                    'transitions' => $storyboardData['transitions'] ?? [],
                    'total_duration' => $storyboardData['total_estimated_duration'] ?? ''
                ]
            ]);
            
        } catch (Exception $e) {
            Log::error('Storyboard generation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate storyboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Optimize an existing prompt
     */
    public function optimizePrompt(Request $request): JsonResponse
    {
        $request->validate([
            'prompt' => 'required|string',
            'target_model' => 'nullable|in:stable_diffusion,midjourney,dalle,gemini',
            'project_id' => 'nullable|uuid|exists:projects,id'
        ]);
        
        try {
            $targetModel = $request->input('target_model', 'stable_diffusion');
            
            $prompt = <<<PROMPT
Optimize this prompt for {$targetModel}:

Original Prompt: {$request->input('prompt')}

Improve it by:
1. Adding specific style descriptors
2. Including quality tags
3. Adding lighting and atmosphere
4. Including composition guidance
5. Making it more detailed and specific

Return as JSON:
{
    "optimized_prompt": "the fully optimized prompt",
    "improvements": ["array of improvements made"],
    "model_specific_adjustments": "any {$targetModel}-specific changes",
    "alternatives": ["3 alternative versions"]
}

JSON output only:
PROMPT;
            
            $result = $this->geminiService->generateContent($prompt, [
                'temperature' => 0.7,
                'max_tokens' => 2048
            ]);
            
            if (!$result['success']) {
                throw new Exception($result['error'] ?? 'Failed to optimize prompt');
            }
            
            $optimized = $this->parseJsonResponse($result['content']);
            
            $promptRecord = GeneratedPrompt::create([
                'project_id' => $request->project_id,
                'prompt_type' => 'visual_thumbnail',
                'input_keywords' => $request->input('prompt'),
                'generated_prompt' => $optimized['optimized_prompt'] ?? '',
                'ai_response' => $result['content'],
                'variations' => $optimized['alternatives'] ?? [],
                'metadata' => [
                    'target_model' => $targetModel,
                    'improvements' => $optimized['improvements'] ?? []
                ],
                'status' => 'completed',
                'processing_time_ms' => $result['processing_time_ms'] ?? 0
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'original_prompt' => $request->input('prompt'),
                    'optimized_prompt' => $optimized['optimized_prompt'] ?? '',
                    'improvements' => $optimized['improvements'] ?? [],
                    'alternatives' => $optimized['alternatives'] ?? []
                ]
            ]);
            
        } catch (Exception $e) {
            Log::error('Prompt optimization error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to optimize prompt',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get all prompts for a project
     */
    public function getProjectPrompts(string $projectId): JsonResponse
    {
        try {
            $prompts = GeneratedPrompt::where('project_id', $projectId)
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $prompts
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch project prompts',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get prompt details
     */
    public function getPromptDetails(string $promptId): JsonResponse
    {
        try {
            $prompt = GeneratedPrompt::findOrFail($promptId);
            
            return response()->json([
                'success' => true,
                'data' => $prompt
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Prompt not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
    
    // ============================================
    // WORKFLOW 4: VIDEO SCRIPT GENERATION
    // ============================================
    
    /**
     * Generate complete video script (called sequentially after title)
     */
    public function generateScript(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:200',
            'context' => 'nullable|string|max:5000',
            'script_type' => 'nullable|in:tutorial,review,vlog,educational,entertainment,shorts,livestream',
            'tone' => 'nullable|in:professional,casual,humorous,serious,inspirational,technical',
            'platform' => 'nullable|in:youtube,tiktok,instagram,all',
            'project_id' => 'nullable|uuid|exists:projects,id'
        ]);
        
        try {
            DB::beginTransaction();
            
            // Update project status
            if ($request->project_id) {
                Project::where('id', $request->project_id)
                    ->update(['status' => 'script_generating']);
            }
            
            // Generate script using Gemini
            $result = $this->geminiService->generateVideoScript(
                $request->input('title'),
                $request->input('context', ''),
                $request->input('script_type', 'tutorial'),
                $request->input('tone', 'casual')
            );
            
            if (!$result['success']) {
                throw new Exception($result['error'] ?? 'Failed to generate script');
            }
            
            $scriptData = $this->parseJsonResponse($result['content']);
            
            // Create video script record
            $script = VideoScript::create([
                'project_id' => $request->project_id,
                'title' => $scriptData['title'] ?? $request->input('title'),
                'hook' => $scriptData['hook'] ?? '',
                'script_content' => $this->formatScriptContent($scriptData),
                'description' => $scriptData['technical']['estimated_duration'] ?? '',
                'call_to_action' => $scriptData['conclusion']['cta'] ?? '',
                'script_type' => $request->input('script_type', 'tutorial'),
                'tone' => $request->input('tone', 'casual'),
                'estimated_duration_seconds' => $this->parseDuration($scriptData['technical']['estimated_duration'] ?? ''),
                'word_count' => $scriptData['technical']['word_count'] ?? 0,
                'metadata' => $scriptData,
                'status' => 'completed'
            ]);
            
            // Create script scenes if available
            if (!empty($scriptData['segments'])) {
                foreach ($scriptData['segments'] as $index => $segment) {
                    $script->scenes()->create([
                        'scene_number' => $index + 1,
                        'scene_title' => $segment['title'] ?? "Scene " . ($index + 1),
                        'narration' => $segment['script'] ?? '',
                        'visual_direction' => implode(', ', $segment['b_roll'] ?? []),
                        'duration_seconds' => 30, // Default estimate
                        'notes' => json_encode($segment)
                    ]);
                }
            }
            
            // Create SEO optimization entry
            $seo = SeoOptimization::create([
                'project_id' => $request->project_id,
                'video_script_id' => $script->id,
                'final_title' => $script->title,
                'original_title' => $request->input('title'),
                'recommended_tags' => [],
                'status' => 'pending'
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Script generated successfully',
                'data' => [
                    'script_id' => $script->id,
                    'title' => $script->title,
                    'hook' => $script->hook,
                    'script_content' => $script->script_content,
                    'segments' => $scriptData['segments'] ?? [],
                    'conclusion' => $scriptData['conclusion'] ?? [],
                    'technical' => $scriptData['technical'] ?? [],
                    'scenes_count' => $script->scenes()->count(),
                    'processing_time_ms' => $result['processing_time_ms'] ?? 0
                ]
            ]);
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Script generation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate script',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Analyze keywords for better generation
     */
    public function analyzeKeywords(Request $request): JsonResponse
    {
        $request->validate([
            'keywords' => 'required|string|max:1000'
        ]);
        
        try {
            $keywords = $request->input('keywords');
            
            $prompt = <<<PROMPT
Analyze these keywords for content creation:

Keywords: {$keywords}

Provide:
1. Main topic identification
2. Related keyword clusters
3. Search intent (informational, transactional, navigational)
4. Content angle suggestions
5. Audience demographics

Return as JSON:
{
    "main_topic": "primary topic",
    "related_keywords": ["array of related keywords"],
    "search_intent": "primary intent",
    "content_angles": ["array of possible angles"],
    "target_audience": "description",
    "difficulty_score": 1-100,
    "opportunity_score": 1-100
}

JSON output only:
PROMPT;
            
            $result = $this->geminiService->generateContent($prompt, [
                'temperature' => 0.5,
                'max_tokens' => 2048
            ]);
            
            if (!$result['success']) {
                throw new Exception($result['error'] ?? 'Failed to analyze keywords');
            }
            
            $analysis = $this->parseJsonResponse($result['content']);
            
            return response()->json([
                'success' => true,
                'data' => $analysis
            ]);
            
        } catch (Exception $e) {
            Log::error('Keyword analysis error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to analyze keywords',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // ============================================
    // AI PROVIDER & WORKFLOW MANAGEMENT
    // ============================================
    
    /**
     * Get AI provider status
     */
    public function getProviderStatus(): JsonResponse
    {
        $testResult = $this->geminiService->testConnection();
        
        return response()->json([
            'success' => true,
            'data' => [
                'gemini' => [
                    'status' => $testResult['success'] ? 'online' : 'offline',
                    'model' => $testResult['model'] ?? 'gemini-pro',
                    'last_tested' => now()->toISOString(),
                    'message' => $testResult['message']
                ]
            ]
        ]);
    }
    
    /**
     * Test Gemini API connection
     */
    public function testGeminiConnection(): JsonResponse
    {
        $result = $this->geminiService->testConnection();
        
        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result
        ]);
    }
    
    /**
     * Get usage statistics
     */
    public function getUsageStats(): JsonResponse
    {
        try {
            $stats = [
                'total_prompts' => GeneratedPrompt::count(),
                'total_successful' => GeneratedPrompt::where('status', 'completed')->count(),
                'total_failed' => GeneratedPrompt::where('status', 'failed')->count(),
                'total_thumbnails' => Thumbnail::count(),
                'total_scripts' => VideoScript::count(),
                'total_projects' => Project::count(),
                'by_type' => GeneratedPrompt::select('prompt_type', DB::raw('count(*) as count'))
                    ->groupBy('prompt_type')
                    ->pluck('count', 'prompt_type'),
                'avg_processing_time' => GeneratedPrompt::where('status', 'completed')->avg('processing_time_ms')
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch usage stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Start automated workflow
     */
    public function startWorkflow(Request $request): JsonResponse
    {
        $request->validate([
            'project_id' => 'required|uuid|exists:projects,id',
            'start_from' => 'nullable|in:keywords,titles,prompts,script,seo'
        ]);
        
        try {
            $project = Project::findOrFail($request->project_id);
            $startFrom = $request->input('start_from', 'keywords');
            
            // This would typically be handled by a queue job
            // For now, return workflow status
            return response()->json([
                'success' => true,
                'message' => 'Workflow started',
                'data' => [
                    'project_id' => $project->id,
                    'workflow_id' => Str::uuid()->toString(),
                    'started_from' => $startFrom,
                    'status' => 'in_progress'
                ]
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start workflow',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get workflow status
     */
    public function getWorkflowStatus(string $projectId): JsonResponse
    {
        try {
            $project = Project::with([
                'prompts',
                'thumbnails',
                'videoScript',
                'seoOptimization'
            ])->findOrFail($projectId);
            
            $workflowSteps = [
                [
                    'step' => 1,
                    'name' => 'Title Generation',
                    'status' => $project->prompts->where('prompt_type', 'title_idea')->count() > 0 ? 'completed' : 'pending',
                    'data' => $project->prompts->where('prompt_type', 'title_idea')->first()
                ],
                [
                    'step' => 2,
                    'name' => 'Prompt Engineering',
                    'status' => $project->prompts->where('prompt_type', '!=', 'title_idea')->count() > 0 ? 'completed' : 'pending',
                    'data' => $project->prompts->where('prompt_type', '!=', 'title_idea')->first()
                ],
                [
                    'step' => 3,
                    'name' => 'Thumbnail Generation',
                    'status' => $project->thumbnails->count() > 0 ? 'completed' : 'pending',
                    'data' => $project->thumbnails->first()
                ],
                [
                    'step' => 4,
                    'name' => 'Script Creation',
                    'status' => $project->videoScript ? 'completed' : 'pending',
                    'data' => $project->videoScript
                ],
                [
                    'step' => 5,
                    'name' => 'SEO Optimization',
                    'status' => $project->seoOptimization ? 'completed' : 'pending',
                    'data' => $project->seoOptimization
                ]
            ];
            
            return response()->json([
                'success' => true,
                'data' => [
                    'project_id' => $project->id,
                    'project_status' => $project->status,
                    'steps' => $workflowSteps,
                    'completion_percentage' => collect($workflowSteps)->where('status', 'completed')->count() / 5 * 100
                ]
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch workflow status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Cancel running workflow
     */
    public function cancelWorkflow(string $projectId): JsonResponse
    {
        try {
            Project::where('id', $projectId)->update(['status' => 'draft']);
            
            return response()->json([
                'success' => true,
                'message' => 'Workflow cancelled'
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel workflow',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // ============================================
    // HELPER METHODS
    // ============================================
    
    /**
     * Parse JSON from AI response
     */
    private function parseJsonResponse(string $content): array
    {
        // Try to extract JSON from markdown code blocks first
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $content, $matches)) {
            $content = $matches[1];
        }
        
        // Try to find JSON object or array
        if (preg_match('/(\{[\s\S]*\}|\[[\s\S]*\])/', $content, $matches)) {
            $json = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $json;
            }
        }
        
        // Try direct parsing
        $json = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }
        
        Log::warning('Failed to parse JSON from AI response', ['content' => substr($content, 0, 500)]);
        return [];
    }
    
    /**
     * Format script content for storage
     */
    private function formatScriptContent(array $scriptData): string
    {
        $content = [];
        
        if (!empty($scriptData['hook'])) {
            $content[] = "🎬 HOOK\n" . $scriptData['hook'];
        }
        
        if (!empty($scriptData['segments'])) {
            foreach ($scriptData['segments'] as $segment) {
                $content[] = "\n📍 " . ($segment['title'] ?? 'Segment');
                $content[] = $segment['script'] ?? '';
                
                if (!empty($segment['b_roll'])) {
                    $content[] = "[B-ROLL: " . implode(', ', $segment['b_roll']) . "]";
                }
            }
        }
        
        if (!empty($scriptData['conclusion'])) {
            $content[] = "\n🎯 CONCLUSION\n" . ($scriptData['conclusion']['summary'] ?? '');
            $content[] = "\n📢 CTA\n" . ($scriptData['conclusion']['cta'] ?? '');
        }
        
        return implode("\n\n", $content);
    }
    
    /**
     * Parse duration string to seconds
     */
    private function parseDuration(string $duration): int
    {
        if (preg_match('/(\d+)\s*min(?:ute)?s?\s*(?:(\d+)\s*sec(?:ond)?s?)?/', $duration, $matches)) {
            $minutes = (int) $matches[1];
            $seconds = isset($matches[2]) ? (int) $matches[2] : 0;
            return ($minutes * 60) + $seconds;
        }
        
        if (preg_match('/(\d+):(\d+)/', $duration, $matches)) {
            return ((int) $matches[1] * 60) + (int) $matches[2];
        }
        
        return 0;
    }
}
