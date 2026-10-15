<?php

namespace App\Http\Controllers;

use App\Models\CommunityReport;
use App\Models\Topic;
use App\Models\Video;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GalleryController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Video::where('is_public', true)
            ->where('moderation_status', 'approved')
            ->where('status', 'completed')
            ->with(['user', 'topic']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('topic_name', 'like', "%{$search}%")
                  ->orWhere('summary_text', 'like', "%{$search}%");
            });
        }

        if ($topicId = $request->input('topic_id')) {
            $query->where('topic_id', $topicId);
        }

        $videos = $query->orderByDesc('views_count')->paginate(12)->withQueryString();
        $topics = Topic::orderBy('title')->get();

        return Inertia::render('Gallery/Index', [
            'videos' => $videos,
            'topics' => $topics,
            'filters' => $request->only(['search', 'topic_id']),
        ]);
    }

    public function report(Request $request, Video $video): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        CommunityReport::create([
            'user_id' => $request->user()->id,
            'video_id' => $video->id,
            'reason' => $validated['reason'],
            'notes' => $validated['notes'] ?? '',
            'status' => 'pending',
        ]);

        return back()->with('success', 'Laporan Anda telah diterima dan akan ditinjau oleh tim moderasi.');
    }
}
