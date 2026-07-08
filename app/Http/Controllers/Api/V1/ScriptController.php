<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\VideoScript;
use App\Models\ScriptScene;
use App\Models\Project;
use App\Services\GeminiAiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ScriptController extends Controller
{
    private GeminiAiService $geminiService;
    
    public function __construct(GeminiAiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }
    
    /**
     * Generate video script
     */
    public function generateScript(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:200',
            'context' => 'nullable|string|max:5000',
            'script_type' => 'nullable|in:tutorial,review,vlog,educational,entertainment,shorts,livestream',
            'tone' => 'nullable|in:professional,casual,humorous,serious,inspirational,technical',
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
                'status' => VideoScript::STATUS_COMPLETED
            ]);
            
            // Create scenes
            if (!empty($scriptData['segments'])) {
                foreach ($scriptData['segments'] as $index => $segment) {
                    $script->scenes()->create([
                        'scene_number' => $index + 1,
                        'scene_title' => $segment['title'] ?? "Scene " . ($index + 1),
                        'narration' => $segment['script'] ?? '',
                        'visual_direction' => implode(', ', $segment['b_roll'] ?? []),
                        'duration_seconds' => 30,
                        'is_generated' => true
                    ]);
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Script generated successfully',
                'data' => [
                    'script' => $script->load('scenes'),
                    'segments' => $scriptData['segments'] ?? [],
                    'conclusion' => $scriptData['conclusion'] ?? []
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
     * Generate script from existing prompt
     */
    public function generateFromPrompt(Request $request): JsonResponse
    {
        $request->validate([
            'prompt_id' => 'required|uuid|exists:generated_prompts,id',
            'script_type' => 'nullable|in:tutorial,review,vlog,educational,entertainment,shorts,livestream',
            'tone' => 'nullable|in:professional,casual,humorous,serious,inspirational,technical'
        ]);
        
        try {
            $prompt = \App\Models\GeneratedPrompt::findOrFail($request->prompt_id);
            
            return $this->generateScript(new Request([
                'title' => $prompt->input_keywords,
                'context' => $prompt->generated_prompt,
                'script_type' => $request->input('script_type', 'tutorial'),
                'tone' => $request->input('tone', 'casual'),
                'project_id' => $prompt->project_id
            ]));
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate from prompt',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get script details
     */
    public function show(string $scriptId): JsonResponse
    {
        try {
            $script = VideoScript::with(['scenes.thumbnail', 'project'])->findOrFail($scriptId);
            
            return response()->json([
                'success' => true,
                'data' => $script
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Script not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
    
    /**
     * Update script
     */
    public function update(Request $request, string $scriptId): JsonResponse
    {
        $request->validate([
            'title' => 'nullable|string|max:200',
            'hook' => 'nullable|string',
            'script_content' => 'nullable|string',
            'call_to_action' => 'nullable|string',
            'status' => 'nullable|in:draft,reviewing,approved,rejected'
        ]);
        
        try {
            $script = VideoScript::findOrFail($scriptId);
            $script->update($request->only([
                'title', 'hook', 'script_content', 
                'call_to_action', 'status'
            ]));
            
            return response()->json([
                'success' => true,
                'message' => 'Script updated',
                'data' => $script
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update script',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete script
     */
    public function destroy(string $scriptId): JsonResponse
    {
        try {
            $script = VideoScript::findOrFail($scriptId);
            $script->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Script deleted'
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete script',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get scenes for a script
     */
    public function getScenes(string $scriptId): JsonResponse
    {
        try {
            $scenes = ScriptScene::where('video_script_id', $scriptId)
                ->orderBy('scene_number')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $scenes
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch scenes',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Add scene to script
     */
    public function addScene(Request $request, string $scriptId): JsonResponse
    {
        $request->validate([
            'scene_title' => 'required|string|max:200',
            'narration' => 'nullable|string',
            'visual_direction' => 'nullable|string',
            'duration_seconds' => 'nullable|integer|min:1'
        ]);
        
        try {
            $script = VideoScript::findOrFail($scriptId);
            $lastScene = $script->scenes()->orderBy('scene_number', 'desc')->first();
            
            $scene = $script->scenes()->create([
                'scene_number' => ($lastScene->scene_number ?? 0) + 1,
                'scene_title' => $request->input('scene_title'),
                'narration' => $request->input('narration', ''),
                'visual_direction' => $request->input('visual_direction', ''),
                'duration_seconds' => $request->input('duration_seconds', 30)
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Scene added',
                'data' => $scene
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add scene',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update scene
     */
    public function updateScene(Request $request, string $sceneId): JsonResponse
    {
        $request->validate([
            'scene_title' => 'nullable|string|max:200',
            'narration' => 'nullable|string',
            'visual_direction' => 'nullable|string',
            'shot_type' => 'nullable|in:wide_shot,medium_shot,close_up,extreme_close_up,pov,over_shoulder,aerial,tracking,static',
            'duration_seconds' => 'nullable|integer|min:1'
        ]);
        
        try {
            $scene = ScriptScene::findOrFail($sceneId);
            $scene->update($request->only([
                'scene_title', 'narration', 'visual_direction',
                'shot_type', 'duration_seconds'
            ]));
            
            return response()->json([
                'success' => true,
                'message' => 'Scene updated',
                'data' => $scene
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update scene',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete scene
     */
    public function deleteScene(string $sceneId): JsonResponse
    {
        try {
            $scene = ScriptScene::findOrFail($sceneId);
            $scriptId = $scene->video_script_id;
            $scene->delete();
            
            // Reorder remaining scenes
            $remainingScenes = ScriptScene::where('video_script_id', $scriptId)
                ->orderBy('scene_number')
                ->get();
            
            foreach ($remainingScenes as $index => $s) {
                $s->update(['scene_number' => $index + 1]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Scene deleted'
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete scene',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Generate thumbnail for scene
     */
    public function generateSceneThumbnail(string $sceneId): JsonResponse
    {
        try {
            $scene = ScriptScene::with('videoScript')->findOrFail($sceneId);
            
            // Generate thumbnail based on scene content
            $thumbnailController = new ThumbnailController($this->geminiService);
            $result = $thumbnailController->generateThumbnail(new Request([
                'prompt' => $scene->scene_title . ' - ' . $scene->visual_direction,
                'style' => 'concept_art',
                'project_id' => $scene->videoScript->project_id
            ]));
            
            if ($result->getData()->success) {
                $thumbnailData = $result->getData()->data;
                $scene->update([
                    'thumbnail_id' => $thumbnailData->id,
                    'thumbnail_url' => $thumbnailData->image_url
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Scene thumbnail generated',
                    'data' => $scene
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate thumbnail'
            ], 500);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate scene thumbnail',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get script templates
     */
    public function getTemplates(): JsonResponse
    {
        $templates = [
            ['id' => 'tutorial', 'name' => 'Tutorial', 'description' => 'Step-by-step instructional content'],
            ['id' => 'review', 'name' => 'Review', 'description' => 'Product/service review format'],
            ['id' => 'listicle', 'name' => 'Listicle', 'description' => 'Numbered list format (Top 10, 5 Ways, etc.)'],
            ['id' => 'storytime', 'name' => 'Story Time', 'description' => 'Personal story narrative'],
            ['id' => 'educational', 'name' => 'Educational', 'description' => 'Learning-focused content'],
            ['id' => 'comparison', 'name' => 'Comparison', 'description' => 'Side-by-side comparison format']
        ];
        
        return response()->json([
            'success' => true,
            'data' => $templates
        ]);
    }
    
    /**
     * Export script
     */
    public function exportScript(string $scriptId): JsonResponse
    {
        try {
            $script = VideoScript::with('scenes')->findOrFail($scriptId);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'title' => $script->title,
                    'hook' => $script->hook,
                    'script' => $script->script_content,
                    'cta' => $script->call_to_action,
                    'scenes' => $script->scenes,
                    'metadata' => [
                        'type' => $script->type_label,
                        'tone' => $script->tone_label,
                        'duration' => $script->formatted_duration,
                        'word_count' => $script->word_count
                    ]
                ]
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export script',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Parse JSON from response
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
     * Format script content
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
