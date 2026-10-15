<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Models\Video;
use App\Models\VideoBookmark;
use App\Models\VideoQuiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VideoController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = Video::where('user_id', $user->id)->with('topic');

        // Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('topic_name', 'like', "%{$search}%")
                  ->orWhere('summary_text', 'like', "%{$search}%");
            });
        }

        // Topic filter
        if ($topicId = $request->input('topic_id')) {
            $query->where('topic_id', $topicId);
        }

        // Status filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Sorting
        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'oldest' => $query->orderBy('created_at', 'asc'),
            'duration' => $query->orderByDesc('duration_seconds'),
            default => $query->orderByDesc('created_at'),
        };

        $videos = $query->paginate(12)->withQueryString();
        $topics = Topic::orderBy('title')->get();

        return Inertia::render('Library/Index', [
            'videos' => $videos,
            'topics' => $topics,
            'filters' => $request->only(['search', 'topic_id', 'status', 'sort']),
        ]);
    }

    public function show(Request $request, Video $video): Response
    {
        // Check authorization: video belongs to user, or user is admin, or video is public
        if ($video->user_id !== $request->user()?->id && !$video->is_public && !$request->user()?->isAdmin()) {
            abort(403, 'Anda tidak memiliki hak untuk melihat video pribadi ini.');
        }

        // Increment views
        $video->increment('views_count');

        $video->load(['topic', 'scenes.source', 'quizzes', 'series']);

        $isBookmarked = false;
        if ($request->user()) {
            $isBookmarked = VideoBookmark::where('user_id', $request->user()->id)
                ->where('video_id', $video->id)
                ->exists();
        }

        return Inertia::render('Videos/Show', [
            'video' => $video,
            'isBookmarked' => $isBookmarked,
            'isOwner' => $request->user()?->id === $video->user_id,
            'streamUrl' => route('videos.stream', $video->id),
        ]);
    }

    public function stream(Video $video): BinaryFileResponse
    {
        if (!$video->video_path) {
            abort(404, 'Berkas video belum tersedia.');
        }

        $path = storage_path('app/private/' . $video->video_path);
        if (!file_exists($path)) {
            // Check public fallback for demo
            $publicPath = public_path('storage/' . $video->video_path);
            if (file_exists($publicPath)) {
                $path = $publicPath;
            } else {
                abort(404, 'Berkas media tidak ditemukan di server.');
            }
        }

        return response()->file($path, [
            'Content-Type' => 'video/mp4',
            'Accept-Ranges' => 'bytes',
        ]);
    }

    public function download(Request $request, Video $video, string $type = 'video')
    {
        if ($video->user_id !== $request->user()?->id && !$video->is_public && !$request->user()?->isAdmin()) {
            abort(403);
        }

        $video->increment('downloads_count');

        return match ($type) {
            'srt' => $this->downloadFile(
                storage_path('app/private/' . $video->subtitles_srt_path),
                Str::slug($video->title) . '.srt',
                'text/plain'
            ),
            'vtt' => $this->downloadFile(
                storage_path('app/private/' . $video->subtitles_vtt_path),
                Str::slug($video->title) . '.vtt',
                'text/vtt'
            ),
            'thumbnail' => $this->downloadFile(
                storage_path('app/public/' . $video->thumbnail_path),
                Str::slug($video->title) . '-thumbnail.jpg',
                'image/jpeg'
            ),
            'transcript' => response($video->transcript_text ?? $video->summary_text, 200, [
                'Content-Type' => 'text/plain; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . Str::slug($video->title) . '-transkrip.txt"',
            ]),
            default => $this->downloadFile(
                storage_path('app/private/' . $video->video_path),
                Str::slug($video->title) . '-30s.mp4',
                'video/mp4'
            ),
        };
    }

    protected function downloadFile(string $fullPath, string $downloadName, string $mimeType)
    {
        if (!file_exists($fullPath)) {
            abort(404, 'Berkas unduhan tidak ditemukan di server.');
        }

        return response()->download($fullPath, $downloadName, [
            'Content-Type' => $mimeType,
        ]);
    }

    public function toggleBookmark(Request $request, Video $video): JsonResponse
    {
        $user = $request->user();
        $bookmark = VideoBookmark::where('user_id', $user->id)->where('video_id', $video->id)->first();

        if ($bookmark) {
            $bookmark->delete();
            return response()->json(['bookmarked' => false, 'message' => 'Video dihapus dari bookmark.']);
        }

        VideoBookmark::create([
            'user_id' => $user->id,
            'video_id' => $video->id,
        ]);

        return response()->json(['bookmarked' => true, 'message' => 'Video disimpan ke bookmark.']);
    }

    public function submitQuiz(Request $request, Video $video): JsonResponse
    {
        $answers = $request->input('answers', []);
        $quizzes = $video->quizzes()->get();

        $score = 0;
        $total = $quizzes->count();
        $results = [];

        foreach ($quizzes as $quiz) {
            $userAnswer = $answers[$quiz->id] ?? null;
            $isCorrect = ($userAnswer !== null && (int)$userAnswer === $quiz->correct_option_index);
            if ($isCorrect) $score++;

            $results[] = [
                'quiz_id' => $quiz->id,
                'is_correct' => $isCorrect,
                'correct_index' => $quiz->correct_option_index,
                'explanation' => $quiz->explanation,
            ];
        }

        return response()->json([
            'score' => $score,
            'total' => $total,
            'percentage' => $total > 0 ? round(($score / $total) * 100) : 100,
            'results' => $results,
        ]);
    }

    public function togglePublic(Request $request, Video $video): RedirectResponse
    {
        if ($video->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            abort(403);
        }

        $newStatus = !$video->is_public;
        $video->update([
            'is_public' => $newStatus,
            'moderation_status' => $newStatus ? 'pending' : 'approved',
        ]);

        $msg = $newStatus
            ? 'Video diajukan ke Galeri Komunitas (menunggu moderasi).'
            : 'Video kini diubah menjadi pribadi.';

        return back()->with('success', $msg);
    }

    public function destroy(Request $request, Video $video): RedirectResponse
    {
        if ($video->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            abort(403);
        }

        // Delete associated files
        if ($video->video_path) Storage::disk('local')->delete('private/' . $video->video_path);
        if ($video->thumbnail_path) Storage::disk('public')->delete($video->thumbnail_path);
        if ($video->subtitles_srt_path) Storage::disk('local')->delete('private/' . $video->subtitles_srt_path);
        if ($video->subtitles_vtt_path) Storage::disk('local')->delete('private/' . $video->subtitles_vtt_path);

        $video->delete();

        return redirect()->route('library.index')->with('success', 'Video berhasil dihapus.');
    }
}
