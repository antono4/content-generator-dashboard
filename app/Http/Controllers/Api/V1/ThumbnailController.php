<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Thumbnail;
use App\Models\Project;
use App\Services\GeminiAiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Storage;

class ThumbnailController extends Controller
{
    private GeminiAiService $geminiService;
    
    public function __construct(GeminiAiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }
    
    /**
     * Generate a single thumbnail
     */
    public function generateThumbnail(Request $request): JsonResponse
    {
        $request->validate([
            'prompt' => 'required|string|max:2000',
            'style' => 'nullable|in:minimalist,bold_text,character_focused,text_overlay,concept_art,photographic',
            'project_id' => 'nullable|uuid|exists:projects,id'
        ]);
        
        try {
            DB::beginTransaction();
            
            // Generate visual prompt first
            $promptResult = $this->geminiService->generateVisualPrompt(
                $request->input('prompt'),
                $request->input('style', 'bold_text')
            );
            
            if (!$promptResult['success']) {
                throw new Exception($promptResult['error'] ?? 'Failed to generate prompt');
            }
            
            // Create thumbnail record
            $thumbnail = Thumbnail::create([
                'project_id' => $request->project_id,
                'title' => $request->input('prompt'),
                'ai_provider' => 'gemini',
                'style' => $request->input('style', 'bold_text'),
                'status' => Thumbnail::STATUS_GENERATING,
                'generation_params' => [
                    'prompt' => $promptResult['content'],
                    'style' => $request->input('style')
                ],
                'width' => 1280,
                'height' => 720
            ]);
            
            // Note: In production, you would call the actual image generation API here
            // For now, we'll mark it as ready with a placeholder
            $thumbnail->update([
                'status' => Thumbnail::STATUS_READY,
                'image_url' => $this->generatePlaceholderUrl($thumbnail->id)
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Thumbnail generated successfully',
                'data' => $thumbnail
            ]);
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Thumbnail generation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate thumbnail',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Generate batch of thumbnails
     */
    public function generateBatch(Request $request): JsonResponse
    {
        $request->validate([
            'prompt' => 'required|string|max:2000',
            'style' => 'nullable|in:minimalist,bold_text,character_focused,text_overlay,concept_art,photographic',
            'count' => 'nullable|integer|min:1|max:5',
            'project_id' => 'nullable|uuid|exists:projects,id'
        ]);
        
        try {
            $count = $request->input('count', 3);
            $thumbnails = [];
            
            for ($i = 0; $i < $count; $i++) {
                $result = $this->generateThumbnail(new Request([
                    'prompt' => $request->input('prompt'),
                    'style' => $request->input('style', 'bold_text'),
                    'project_id' => $request->project_id
                ]));
                
                if ($result->getData()->success) {
                    $thumbnails[] = $result->getData()->data;
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Generated {$count} thumbnails",
                'data' => [
                    'thumbnails' => $thumbnails,
                    'count' => count($thumbnails)
                ]
            ]);
            
        } catch (Exception $e) {
            Log::error('Batch thumbnail generation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate thumbnails',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get thumbnail details
     */
    public function show(string $thumbnailId): JsonResponse
    {
        try {
            $thumbnail = Thumbnail::with(['project', 'prompt'])->findOrFail($thumbnailId);
            
            return response()->json([
                'success' => true,
                'data' => $thumbnail
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Thumbnail not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
    
    /**
     * Update thumbnail metadata
     */
    public function update(Request $request, string $thumbnailId): JsonResponse
    {
        $request->validate([
            'title' => 'nullable|string|max:200',
            'style' => 'nullable|in:minimalist,bold_text,character_focused,text_overlay,concept_art,photographic'
        ]);
        
        try {
            $thumbnail = Thumbnail::findOrFail($thumbnailId);
            $thumbnail->update($request->only(['title', 'style']));
            
            return response()->json([
                'success' => true,
                'message' => 'Thumbnail updated',
                'data' => $thumbnail
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update thumbnail',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete thumbnail
     */
    public function destroy(string $thumbnailId): JsonResponse
    {
        try {
            $thumbnail = Thumbnail::findOrFail($thumbnailId);
            $thumbnail->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Thumbnail deleted'
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete thumbnail',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Regenerate thumbnail
     */
    public function regenerate(string $thumbnailId): JsonResponse
    {
        try {
            $thumbnail = Thumbnail::findOrFail($thumbnailId);
            
            // Generate new thumbnail
            $result = $this->generateThumbnail(new Request([
                'prompt' => $thumbnail->title,
                'style' => $thumbnail->style,
                'project_id' => $thumbnail->project_id
            ]));
            
            if ($result->getData()->success) {
                // Update version
                $thumbnail->update([
                    'version' => $thumbnail->version + 1
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Thumbnail regenerated',
                    'data' => $result->getData()->data
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate'
            ], 500);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate thumbnail',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Select thumbnail as primary
     */
    public function selectAsPrimary(string $thumbnailId): JsonResponse
    {
        try {
            $thumbnail = Thumbnail::findOrFail($thumbnailId);
            $thumbnail->selectAsPrimary();
            
            return response()->json([
                'success' => true,
                'message' => 'Thumbnail set as primary'
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to select thumbnail',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get all thumbnails for a project
     */
    public function getProjectThumbnails(string $projectId): JsonResponse
    {
        try {
            $thumbnails = Thumbnail::where('project_id', $projectId)
                ->orderBy('is_selected', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $thumbnails
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch thumbnails',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Download thumbnail
     */
    public function downloadThumbnail(string $thumbnailId): JsonResponse
    {
        try {
            $thumbnail = Thumbnail::findOrFail($thumbnailId);
            
            // In production, this would trigger actual download
            return response()->json([
                'success' => true,
                'message' => 'Download started',
                'data' => [
                    'url' => $thumbnail->image_url,
                    'filename' => 'thumbnail-' . $thumbnail->id . '.' . $thumbnail->file_format
                ]
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download thumbnail',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Generate placeholder URL for demo
     */
    private function generatePlaceholderUrl(string $id): string
    {
        // Placeholder service for demo purposes
        return "https://via.placeholder.com/1280x720/1a1a25/6366f1?text=Thumbnail+{$id}";
    }
}
