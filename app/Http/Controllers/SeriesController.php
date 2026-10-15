<?php

namespace App\Http\Controllers;

use App\Models\VideoSeries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SeriesController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $series = VideoSeries::where('user_id', $user->id)
            ->withCount('videos')
            ->orderByDesc('created_at')
            ->paginate(10);

        return Inertia::render('Series/Index', [
            'series' => $series,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'total_episodes' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        VideoSeries::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']) . '-' . Str::random(5),
            'description' => $validated['description'] ?? '',
            'total_episodes' => $validated['total_episodes'],
        ]);

        return back()->with('success', 'Seri video baru berhasil dibuat!');
    }

    public function show(Request $request, VideoSeries $series): Response
    {
        if ($series->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            abort(403);
        }

        $series->load(['videos' => function ($q) {
            $q->orderBy('series_episode');
        }]);

        return Inertia::render('Series/Show', [
            'series' => $series,
        ]);
    }
}
