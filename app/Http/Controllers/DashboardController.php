<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Models\Video;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Recent videos
        $recentVideos = Video::where('user_id', $user->id)
            ->with('topic')
            ->orderByDesc('created_at')
            ->take(6)
            ->get();

        // Active processing jobs
        $activeVideos = Video::where('user_id', $user->id)
            ->whereIn('status', ['queued', 'preparing_references', 'generating_script', 'waiting_script_approval', 'generating_narration', 'generating_visuals', 'rendering'])
            ->with('scenes')
            ->orderByDesc('updated_at')
            ->get();

        // Stats
        $stats = [
            'total_videos' => Video::where('user_id', $user->id)->count(),
            'completed_videos' => Video::where('user_id', $user->id)->where('status', 'completed')->count(),
            'quota_remaining' => $user->remainingQuota(),
            'quota_total' => $user->quota_total,
            'quota_used' => $user->quota_used,
        ];

        // Recommended topics
        $recommendedTopics = Topic::where('is_popular', true)->inRandomOrder()->take(4)->get();

        return Inertia::render('Dashboard/Index', [
            'stats' => $stats,
            'recentVideos' => $recentVideos,
            'activeVideos' => $activeVideos,
            'recommendedTopics' => $recommendedTopics,
        ]);
    }
}
