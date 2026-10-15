<?php

namespace App\Http\Controllers;

use App\Jobs\ProduceVideoJob;
use App\Models\AppSetting;
use App\Models\StoryboardScene;
use App\Models\Topic;
use App\Models\Video;
use App\Services\GeminiService;
use App\Services\QuotaService;
use App\Services\VideoRenderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class StudioController extends Controller
{
    public function index(Request $request): Response
    {
        $topics = Topic::orderBy('title')->get();
        $selectedTopicId = $request->query('topic_id');
        $activeVideoId = $request->query('video_id');

        $activeVideo = null;
        if ($activeVideoId) {
            $activeVideo = Video::with(['scenes', 'topic'])
                ->where('user_id', $request->user()->id)
                ->where('id', $activeVideoId)
                ->first();
        }

        // Available voice choices
        $voices = [
            ['id' => 'Pari', 'name' => 'Pari (Wanita, Hangat & Bercerita)', 'gender' => 'female'],
            ['id' => 'Kore', 'name' => 'Kore (Pria, Tenang & Mengayomi)', 'gender' => 'male'],
            ['id' => 'Zephyr', 'name' => 'Zephyr (Wanita, Inspiratif & Santai)', 'gender' => 'female'],
            ['id' => 'Fenrir', 'name' => 'Fenrir (Pria, Berwibawa & Reflektif)', 'gender' => 'male'],
        ];

        // Available visual styles
        $visualStyles = [
            ['id' => 'modern_editorial', 'name' => 'Modern Editorial', 'desc' => 'Gaya ilustrasi majalah modern dengan pencahayaan hangat dan estetika rapi.'],
            ['id' => 'balinese_minimalist', 'name' => 'Bali Minimalis', 'desc' => 'Sentuhan motif ornamen janur dan warna tanah dengan garis geometris bersih.'],
            ['id' => 'vibrant_culture', 'name' => 'Budaya Semarak', 'desc' => 'Warna ungu, coral, dan emas yang kaya memancarkan kemegahan tradisi.'],
            ['id' => 'watercolor_art', 'name' => 'Cat Air Suci', 'desc' => 'Sentuhan sapuan kuas halus yang menenangkan batin dan meditatif.'],
        ];

        return Inertia::render('Studio/Index', [
            'topics' => $topics,
            'selectedTopicId' => $selectedTopicId ? (int)$selectedTopicId : null,
            'activeVideo' => $activeVideo,
            'voices' => $voices,
            'visualStyles' => $visualStyles,
            'userQuota' => [
                'total' => $request->user()->quota_total,
                'used' => $request->user()->quota_used,
                'remaining' => $request->user()->remainingQuota(),
            ],
            'geminiConfigured' => AppSetting::has('gemini_api_key'),
        ]);
    }

    public function generate(Request $request, QuotaService $quotaService): RedirectResponse
    {
        $user = $request->user();

        // 1. Quota & Concurrency validation
        $quotaCheck = $quotaService->canCreateVideo($user);
        if (!$quotaCheck['allowed']) {
            return back()->with('error', $quotaCheck['message']);
        }

        // 2. Validate request
        $validated = $request->validate([
            'topic_id' => ['nullable', 'exists:topics,id'],
            'custom_idea' => ['nullable', 'string', 'max:500'],
            'target_audience' => ['required', 'string', 'in:Anak Muda,Remaja,Umum'],
            'tone' => ['required', 'string'],
            'production_mode' => ['required', 'string', 'in:illustration_motion,generative_video'],
            'visual_style' => ['required', 'string'],
            'aspect_ratio' => ['required', 'string', 'in:9:16,16:9,1:1'],
            'voice_id' => ['required', 'string'],
            'has_subtitles' => ['boolean'],
            'review_script_first' => ['boolean'],
        ]);

        $topic = $validated['topic_id'] ? Topic::find($validated['topic_id']) : null;
        $title = $topic ? $topic->title : (Str::limit($validated['custom_idea'] ?: 'Filosofi Hindu', 45));

        // 3. Create Video entity in draft/queued status
        $video = Video::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'title' => $title,
            'topic_id' => $topic?->id,
            'topic_name' => $topic?->title ?: 'Ide Khusus',
            'summary_text' => $validated['custom_idea'] ?? '',
            'target_audience' => $validated['target_audience'],
            'tone' => $validated['tone'],
            'production_mode' => $validated['production_mode'],
            'visual_style' => $validated['visual_style'],
            'aspect_ratio' => $validated['aspect_ratio'],
            'voice_id' => $validated['voice_id'],
            'has_subtitles' => $validated['has_subtitles'] ?? true,
            'status' => 'queued',
            'progress_percent' => 5,
            'current_stage_label' => 'Menunggu antrean pembuatan video 30 detik...',
            'duration_seconds' => 30.00,
        ]);

        $reviewScript = $request->boolean('review_script_first');

        // Dispatch background job (or process immediately if queue sync)
        ProduceVideoJob::dispatch($video, $reviewScript);

        return redirect()->route('studio.index', ['video_id' => $video->id])
            ->with('success', 'Permintaan produksi video 30 detik berhasil dimulai!');
    }

    public function approveScript(Request $request, Video $video): RedirectResponse
    {
        if ($video->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            abort(403);
        }

        if ($video->status !== 'waiting_script_approval') {
            return back()->with('error', 'Status video tidak dalam antrean persetujuan naskah.');
        }

        $video->update([
            'status' => 'queued',
            'current_stage_label' => 'Naskah disetujui, melanjutkan produksi visual & audio...',
            'progress_percent' => 40,
        ]);

        ProduceVideoJob::dispatch($video, false);

        return back()->with('success', 'Naskah disetujui! Produksi media dimulai.');
    }

    public function updateScene(Request $request, Video $video, StoryboardScene $scene): JsonResponse
    {
        if ($video->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['error' => 'Tidak diizinkan.'], 403);
        }

        $validated = $request->validate([
            'narration_text' => ['required', 'string', 'max:500'],
            'onscreen_text' => ['nullable', 'string', 'max:50'],
            'visual_prompt' => ['required', 'string', 'max:500'],
            'camera_motion' => ['required', 'string', 'in:zoom_in,zoom_out,pan_left,pan_right,tilt_up'],
        ]);

        $scene->update($validated);

        // Mark video renders as needing refresh if script changed
        $video->update([
            'status' => 'needs_review',
            'current_stage_label' => 'Perubahan naskah disimpan. Siap untuk diproduksi ulang.',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Perubahan adegan berhasil disimpan.',
            'scene' => $scene,
        ]);
    }

    public function regenerateSceneVisual(Request $request, Video $video, StoryboardScene $scene, GeminiService $gemini): JsonResponse
    {
        if ($video->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['error' => 'Tidak diizinkan.'], 403);
        }

        $res = $gemini->generateSceneVisual(
            $scene->visual_prompt ?: 'Balinese Hindu art',
            $video->visual_style ?: 'modern_editorial',
            $video->aspect_ratio ?: '9:16'
        );

        $scene->update(['image_path' => $res['image_path']]);

        return response()->json([
            'success' => true,
            'message' => 'Visual adegan berhasil dibuat ulang.',
            'image_url' => asset('storage/' . $res['image_path']),
        ]);
    }

    public function regenerateThumbnail(Request $request, Video $video, VideoRenderService $renderer): JsonResponse
    {
        if ($video->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['error' => 'Tidak diizinkan.'], 403);
        }

        if (!$video->video_path) {
            return response()->json(['error' => 'Video belum selesai dirender.'], 400);
        }

        $videoPath = storage_path('app/private/' . $video->video_path);
        $thumbnailRelative = 'thumbnails/' . $video->id . '_' . time() . '.jpg';
        $thumbnailAbsolute = storage_path('app/public/' . $thumbnailRelative);

        $thumbCmd = [
            config('app.ffmpeg_path', 'ffmpeg'),
            '-y',
            '-ss', '00:00:03.000',
            '-i', $videoPath,
            '-vframes', '1',
            '-q:v', '2',
            $thumbnailAbsolute,
        ];

        exec(implode(' ', array_map('escapeshellarg', $thumbCmd)));

        $video->update(['thumbnail_path' => $thumbnailRelative]);

        return response()->json([
            'success' => true,
            'message' => 'Thumbnail berhasil diperbarui.',
            'thumbnail_url' => asset('storage/' . $thumbnailRelative),
        ]);
    }

    public function retry(Request $request, Video $video): RedirectResponse
    {
        if ($video->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            abort(403);
        }

        $video->update([
            'status' => 'queued',
            'current_stage_label' => 'Mengulang tahapan pembuatan video...',
            'error_message' => null,
            'progress_percent' => 15,
        ]);

        ProduceVideoJob::dispatch($video, false);

        return back()->with('success', 'Pembuatan video dijadwalkan ulang.');
    }

    public function cancel(Request $request, Video $video): RedirectResponse
    {
        if ($video->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            abort(403);
        }

        $video->update([
            'status' => 'cancelled',
            'current_stage_label' => 'Proses telah dibatalkan oleh pengguna.',
        ]);

        return back()->with('info', 'Proses pembuatan video dibatalkan.');
    }

    public function getStatus(Video $video): JsonResponse
    {
        return response()->json([
            'id' => $video->id,
            'status' => $video->status,
            'progress_percent' => $video->progress_percent,
            'current_stage_label' => $video->current_stage_label,
            'error_message' => $video->error_message,
            'duration_seconds' => $video->duration_seconds,
            'video_url' => $video->video_path ? route('videos.stream', $video->id) : null,
            'thumbnail_url' => $video->thumbnail_path ? asset('storage/' . $video->thumbnail_path) : null,
        ]);
    }
}
