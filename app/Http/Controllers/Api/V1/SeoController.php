<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SeoOptimization;
use App\Models\Project;
use App\Models\VideoScript;
use App\Services\GeminiAiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class SeoController extends Controller
{
    private GeminiAiService $geminiService;
    
    public function __construct(GeminiAiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }
    
    /**
     * Optimize content for SEO
     */
    public function optimizeContent(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:200',
            'content' => 'nullable|string|max:10000',
            'platform' => 'nullable|in:youtube,tiktok,instagram,all',
            'project_id' => 'nullable|uuid|exists:projects,id'
        ]);
        
        try {
            DB::beginTransaction();
            
            // Update project status
            if ($request->project_id) {
                Project::where('id', $request->project_id)
                    ->update(['status' => 'seo_optimizing']);
            }
            
            // Generate SEO optimization
            $result = $this->geminiService->generateSeoOptimization(
                $request->input('title'),
                $request->input('content', ''),
                $request->input('platform', 'youtube')
            );
            
            if (!$result['success']) {
                throw new Exception($result['error'] ?? 'Failed to optimize SEO');
            }
            
            $seoData = $this->parseJsonResponse($result['content']);
            
            // Create SEO record
            $seo = SeoOptimization::create([
                'project_id' => $request->project_id,
                'final_title' => $seoData['final_title'] ?? $request->input('title'),
                'original_title' => $request->input('title'),
                'title_reasoning' => $seoData['title_changes']['changes_made'] ?? null,
                'recommended_tags' => $seoData['tags']['all_tags'] ?? [],
                'trending_tags' => $seoData['tags']['trending'] ?? [],
                'primary_tag' => $seoData['tags']['primary'] ?? null,
                'secondary_tags' => $seoData['tags']['secondary'] ?? [],
                'competitor_analysis' => $seoData['competitor_analysis'] ?? null,
                'keyword_difficulty' => $seoData['keyword_difficulty'] ?? null,
                'seo_score' => $seoData['optimization']['score'] ?? null,
                'recommendations' => $seoData['optimization']['suggestions'] ?? [],
                'best_posting_time' => $this->parsePostingTime($seoData['posting']['best_time'] ?? null),
                'status' => SeoOptimization::STATUS_COMPLETED
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'SEO optimized successfully',
                'data' => [
                    'seo' => $seo,
                    'final_title' => $seo->final_title,
                    'tags' => $seo->recommended_tags,
                    'optimization' => [
                        'score' => $seo->seo_score,
                        'suggestions' => $seo->recommendations
                    ],
                    'posting' => [
                        'best_time' => $seoData['posting']['best_time'] ?? null,
                        'best_days' => $seoData['posting']['best_days'] ?? []
                    ]
                ]
            ]);
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('SEO optimization error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to optimize SEO',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Generate final optimized title
     */
    public function generateFinalTitle(Request $request): JsonResponse
    {
        $request->validate([
            'original_title' => 'required|string|max:200',
            'keywords' => 'nullable|string|max:500'
        ]);
        
        try {
            $prompt = <<<PROMPT
Optimize this YouTube title for maximum click-through rate and SEO:

Original Title: {$request->input('original_title')}
Keywords: {$request->input('keywords', 'N/A')}

Requirements:
1. Keep under 60 characters
2. Include primary keyword near the beginning
3. Add power words for emotional appeal
4. Create curiosity without clickbait
5. Include numbers if relevant

Return as JSON:
{
    "optimized_title": "the new optimized title",
    "changes": ["array of changes made"],
    "reasoning": "why these changes improve CTR"
}

JSON output only:
PROMPT;
            
            $result = $this->geminiService->generateContent($prompt, [
                'temperature' => 0.7,
                'max_tokens' => 1024
            ]);
            
            if (!$result['success']) {
                throw new Exception($result['error'] ?? 'Failed to generate title');
            }
            
            $data = $this->parseJsonResponse($result['content']);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'original' => $request->input('original_title'),
                    'optimized' => $data['optimized_title'] ?? $request->input('original_title'),
                    'changes' => $data['changes'] ?? [],
                    'reasoning' => $data['reasoning'] ?? ''
                ]
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate title',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Generate SEO tags
     */
    public function generateTags(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:200',
            'content' => 'nullable|string|max:5000',
            'count' => 'nullable|integer|min:5|max:30'
        ]);
        
        try {
            $count = $request->input('count', 15);
            
            $prompt = <<<PROMPT
Generate {$count} relevant SEO tags for this YouTube video:

Title: {$request->input('title')}
Content Summary: {$request->input('content', 'N/A')}

Tags should include:
1. Primary keyword (1)
2. Secondary keywords (3-5)
3. Broader category tags (2-3)
4. Long-tail variations (3-5)
5. Trending/related tags (2-3)
6. Brand/creator tags (1-2)

Return as JSON:
{
    "primary": "main keyword",
    "secondary": ["array of 3-5 secondary keywords"],
    "all_tags": ["complete array of {$count} tags"],
    "trending": ["optional trending tags"],
    "niche": ["niche-specific tags"]
}

JSON output only:
PROMPT;
            
            $result = $this->geminiService->generateContent($prompt, [
                'temperature' => 0.7,
                'max_tokens' => 2048
            ]);
            
            if (!$result['success']) {
                throw new Exception($result['error'] ?? 'Failed to generate tags');
            }
            
            $data = $this->parseJsonResponse($result['content']);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'primary' => $data['primary'] ?? '',
                    'secondary' => $data['secondary'] ?? [],
                    'all_tags' => $data['all_tags'] ?? [],
                    'trending' => $data['trending'] ?? [],
                    'niche' => $data['niche'] ?? []
                ]
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate tags',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Analyze competitors
     */
    public function analyzeCompetitors(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:200',
            'keywords' => 'nullable|string|max:500'
        ]);
        
        try {
            $prompt = <<<PROMPT
Analyze the competitive landscape for this YouTube video:

Title: {$request->input('title')}
Keywords: {$request->input('keywords', 'N/A')}

Provide:
1. Estimated competition level (1-100)
2. Content gaps to exploit
3. Unique angles to stand out
4. Estimated difficulty score

Return as JSON:
{
    "competition_score": 1-100,
    "difficulty": "easy|medium|hard|very_hard",
    "gaps": ["array of content gaps"],
    "unique_angles": ["array of unique angles"],
    "tips": ["array of tips to compete"]
}

JSON output only:
PROMPT;
            
            $result = $this->geminiService->generateContent($prompt, [
                'temperature' => 0.6,
                'max_tokens' => 2048
            ]);
            
            if (!$result['success']) {
                throw new Exception($result['error'] ?? 'Failed to analyze competitors');
            }
            
            $data = $this->parseJsonResponse($result['content']);
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to analyze competitors',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Predict video performance
     */
    public function predictPerformance(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:200',
            'tags' => 'nullable|array',
            'thumbnail_prompt' => 'nullable|string'
        ]);
        
        try {
            $prompt = <<<PROMPT
Predict the potential performance of this YouTube video:

Title: {$request->input('title')}
Tags: {implode(', ', $request->input('tags', []))}
Thumbnail Style: {$request->input('thumbnail_prompt', 'Standard')}

Based on title optimization, tag relevance, and thumbnail appeal, predict:
1. Estimated CTR (Click-Through Rate)
2. Engagement score
3. Virality potential
4. Audience retention estimate

Return as JSON:
{
    "predicted_ctr": "percentage",
    "engagement_score": 1-100,
    "virality_potential": "low|medium|high",
    "retention_estimate": "percentage",
    "factors": {
        "positive": ["positive factors"],
        "negative": ["negative factors"],
        "improvements": ["suggestions to improve"]
    }
}

JSON output only:
PROMPT;
            
            $result = $this->geminiService->generateContent($prompt, [
                'temperature' => 0.5,
                'max_tokens' => 2048
            ]);
            
            if (!$result['success']) {
                throw new Exception($result['error'] ?? 'Failed to predict performance');
            }
            
            $data = $this->parseJsonResponse($result['content']);
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to predict performance',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get SEO data for project
     */
    public function getSeoData(string $projectId): JsonResponse
    {
        try {
            $seo = SeoOptimization::where('project_id', $projectId)
                ->orderBy('created_at', 'desc')
                ->first();
            
            if (!$seo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No SEO data found for this project'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $seo
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch SEO data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update SEO data
     */
    public function updateSeo(Request $request, string $seoId): JsonResponse
    {
        $request->validate([
            'final_title' => 'nullable|string|max:200',
            'primary_tag' => 'nullable|string|max:100',
            'secondary_tags' => 'nullable|array',
            'recommended_tags' => 'nullable|array',
            'best_posting_time' => 'nullable|in:morning,lunch,evening,late_night'
        ]);
        
        try {
            $seo = SeoOptimization::findOrFail($seoId);
            $seo->update($request->only([
                'final_title', 'primary_tag', 
                'secondary_tags', 'recommended_tags',
                'best_posting_time'
            ]));
            
            return response()->json([
                'success' => true,
                'message' => 'SEO updated',
                'data' => $seo
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update SEO',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Apply SEO to project
     */
    public function applySeoToProject(string $projectId): JsonResponse
    {
        try {
            $seo = SeoOptimization::where('project_id', $projectId)
                ->orderBy('created_at', 'desc')
                ->firstOrFail();
            
            $seo->applyToProject();
            
            return response()->json([
                'success' => true,
                'message' => 'SEO applied to project',
                'data' => $seo
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to apply SEO',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Parse JSON response
     */
    private function parseJsonResponse(string $content): array
    {
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $content, $matches)) {
            $content = $matches[1];
        }
        
        if (preg_match('/(\{[\s\S]*\}|\[[\s\S]*\])/', $content, $matches)) {
            $json = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $json;
            }
        }
        
        $json = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }
        
        return [];
    }
    
    /**
     * Parse posting time
     */
    private function parsePostingTime(?string $time): ?string
    {
        if (!$time) return null;
        
        $time = strtolower($time);
        
        if (str_contains($time, 'morning') || str_contains($time, 'am') || (str_contains($time, '6') && str_contains($time, '9'))) {
            return 'morning';
        }
        if (str_contains($time, 'lunch') || str_contains($time, '12') || str_contains($time, '2')) {
            return 'lunch';
        }
        if (str_contains($time, 'evening') || str_contains($time, '5') || str_contains($time, '8')) {
            return 'evening';
        }
        if (str_contains($time, 'night') || str_contains($time, '9') || str_contains($time, '12')) {
            return 'late_night';
        }
        
        return null;
    }
}
