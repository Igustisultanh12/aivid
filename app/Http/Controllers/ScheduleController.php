<?php

namespace App\Http\Controllers;

use App\Models\ScheduledGeneration;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $schedules = ScheduledGeneration::where('user_id', $user->id)
            ->with(['topic', 'executedVideo'])
            ->orderByDesc('scheduled_at')
            ->paginate(10);

        $topics = Topic::orderBy('title')->get();

        return Inertia::render('Schedule/Index', [
            'schedules' => $schedules,
            'topics' => $topics,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'topic_id' => ['nullable', 'exists:topics,id'],
            'custom_idea' => ['nullable', 'string', 'max:500'],
            'target_audience' => ['required', 'string'],
            'tone' => ['required', 'string'],
            'production_mode' => ['required', 'string'],
            'visual_style' => ['required', 'string'],
            'aspect_ratio' => ['required', 'string'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'timezone' => ['required', 'string'],
        ]);

        $topic = $validated['topic_id'] ? Topic::find($validated['topic_id']) : null;

        ScheduledGeneration::create([
            'user_id' => $request->user()->id,
            'topic_id' => $topic?->id,
            'topic_name' => $topic?->title ?: 'Ide Khusus',
            'custom_idea' => $validated['custom_idea'] ?? '',
            'target_audience' => $validated['target_audience'],
            'tone' => $validated['tone'],
            'production_mode' => $validated['production_mode'],
            'visual_style' => $validated['visual_style'],
            'aspect_ratio' => $validated['aspect_ratio'],
            'scheduled_at' => $validated['scheduled_at'],
            'timezone' => $validated['timezone'],
            'status' => 'scheduled',
        ]);

        return back()->with('success', 'Jadwal produksi video berhasil disimpan.');
    }

    public function destroy(Request $request, ScheduledGeneration $schedule): RedirectResponse
    {
        if ($schedule->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            abort(403);
        }

        $schedule->delete();

        return back()->with('success', 'Jadwal pembuatan video berhasil dihapus.');
    }
}
