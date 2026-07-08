<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Main dashboard index
     */
    public function index()
    {
        return view('dashboard.index');
    }
    
    /**
     * Dashboard overview
     */
    public function overview(Request $request)
    {
        $user = $request->user();
        
        $stats = [
            'total_projects' => Project::where('user_id', $user->id)->count(),
            'completed_projects' => Project::where('user_id', $user->id)
                ->where('status', 'completed')->count(),
            'in_progress' => Project::where('user_id', $user->id)
                ->whereNotIn('status', ['draft', 'completed'])->count()
        ];
        
        $recentProjects = Project::where('user_id', $user->id)
            ->with(['primaryThumbnail', 'videoScript'])
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();
        
        return response()->json([
            'stats' => $stats,
            'recent_projects' => $recentProjects
        ]);
    }
    
    /**
     * Title Generator page
     */
    public function titleGenerator()
    {
        return view('dashboard.index');
    }
    
    /**
     * Prompt Engineering page
     */
    public function promptEngineering()
    {
        return view('dashboard.index');
    }
    
    /**
     * Thumbnail Studio page
     */
    public function thumbnailStudio()
    {
        return view('dashboard.index');
    }
    
    /**
     * Script Writer page
     */
    public function scriptWriter()
    {
        return view('dashboard.index');
    }
    
    /**
     * SEO Optimizer page
     */
    public function seoOptimizer()
    {
        return view('dashboard.index');
    }
}
