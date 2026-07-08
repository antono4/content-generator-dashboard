<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class ProjectController extends Controller
{
    /**
     * List all projects
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Project::with(['primaryThumbnail', 'videoScript', 'seoOptimization'])
                ->orderBy('updated_at', 'desc');
            
            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            // Filter by platform
            if ($request->has('platform')) {
                $query->where('platform', $request->platform);
            }
            
            // Filter by user
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            } else {
                $query->where('user_id', auth()->id());
            }
            
            $projects = $query->paginate($request->input('per_page', 15));
            
            return response()->json([
                'success' => true,
                'data' => $projects
            ]);
            
        } catch (Exception $e) {
            Log::error('Project list error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch projects',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Create new project
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:200',
            'primary_keyword' => 'nullable|string|max:200',
            'platform' => 'nullable|in:youtube,tiktok,instagram,all',
            'niche' => 'nullable|string|max:100',
            'notes' => 'nullable|string'
        ]);
        
        try {
            $project = Project::create([
                'user_id' => auth()->id(),
                'title' => $request->input('title'),
                'slug' => Str::slug($request->input('title')) . '-' . Str::random(6),
                'primary_keyword' => $request->input('primary_keyword'),
                'platform' => $request->input('platform', 'youtube'),
                'niche' => $request->input('niche'),
                'notes' => $request->input('notes'),
                'status' => Project::STATUS_DRAFT
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Project created',
                'data' => $project
            ], 201);
            
        } catch (Exception $e) {
            Log::error('Project creation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create project',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get project details
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $project = Project::with([
                'user',
                'prompts',
                'thumbnails',
                'primaryThumbnail',
                'videoScript',
                'videoScript.scenes',
                'seoOptimization'
            ])->findOrFail($uuid);
            
            return response()->json([
                'success' => true,
                'data' => $project,
                'workflow_progress' => $project->workflow_progress
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
    
    /**
     * Update project
     */
    public function update(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'title' => 'nullable|string|max:200',
            'primary_keyword' => 'nullable|string|max:200',
            'status' => 'nullable|in:draft,idea_generating,prompt_generating,thumbnail_generating,script_generating,seo_optimizing,completed',
            'platform' => 'nullable|in:youtube,tiktok,instagram,all',
            'niche' => 'nullable|string|max:100',
            'notes' => 'nullable|string'
        ]);
        
        try {
            $project = Project::findOrFail($uuid);
            $project->update($request->only([
                'title', 'primary_keyword', 'status',
                'platform', 'niche', 'notes'
            ]));
            
            return response()->json([
                'success' => true,
                'message' => 'Project updated',
                'data' => $project
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update project',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete project
     */
    public function destroy(string $uuid): JsonResponse
    {
        try {
            $project = Project::findOrFail($uuid);
            $project->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Project deleted'
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete project',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Duplicate project
     */
    public function duplicate(string $uuid): JsonResponse
    {
        try {
            $original = Project::with([
                'prompts', 'thumbnails', 'videoScript', 'seoOptimization'
            ])->findOrFail($uuid);
            
            DB::beginTransaction();
            
            // Create duplicate project
            $duplicate = $original->replicate();
            $duplicate->title = $original->title . ' (Copy)';
            $duplicate->slug = Str::slug($original->title) . '-copy-' . Str::random(6);
            $duplicate->status = Project::STATUS_DRAFT;
            $duplicate->save();
            
            // Duplicate related data (simplified)
            // In production, you'd want to copy prompts, thumbnails, etc.
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Project duplicated',
                'data' => $duplicate
            ]);
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Project duplication error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate project',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get workflow status
     */
    public function workflowStatus(string $uuid): JsonResponse
    {
        try {
            $project = Project::with([
                'prompts',
                'thumbnails',
                'videoScript',
                'seoOptimization'
            ])->findOrFail($uuid);
            
            $steps = [
                [
                    'step' => 1,
                    'name' => 'Title Generation',
                    'status' => $project->titlePrompts()->exists() ? 'completed' : 'pending',
                    'data' => $project->latestTitlePrompt
                ],
                [
                    'step' => 2,
                    'name' => 'Prompt Engineering',
                    'status' => $project->prompts()->where('prompt_type', '!=', 'title_idea')->exists() ? 'completed' : 'pending',
                    'data' => $project->prompts()->where('prompt_type', '!=', 'title_idea')->first()
                ],
                [
                    'step' => 3,
                    'name' => 'Thumbnail Generation',
                    'status' => $project->thumbnails()->exists() ? 'completed' : 'pending',
                    'data' => $project->thumbnails()->first()
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
                    'steps' => $steps,
                    'completion_percentage' => $project->workflow_progress
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
}
