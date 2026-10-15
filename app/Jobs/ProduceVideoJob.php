<?php

namespace App\Jobs;

use App\Events\VideoStatusUpdated;
use App\Models\Source;
use App\Models\StoryboardScene;
use App\Models\Video;
use App\Models\VideoQuiz;
use App\Services\GeminiService;
use App\Services\VideoRenderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProduceVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 600;

    public function __construct(
        public Video $video,
        public bool $reviewScriptFirst = false
    ) {}

    public function handle(GeminiService $gemini, VideoRenderService $renderer): void
    {
        $video = $this->video->fresh();

        if (in_array($video->status, ['completed', 'cancelled'])) {
            return;
        }

        try {
            // Stage 1: Ambil referensi sumber
            $this->updateStatus($video, 'preparing_references', 10, 'Mengambil sumber referensi filosofi Hindu terverifikasi...');
            
            $sources = Source::where('verification_status', 'verified')->take(3)->get()->toArray();
            sleep(1);

            // Stage 2: Generate Naskah dan Storyboard jika belum ada
            if ($video->scenes()->count() === 0) {
                $this->updateStatus($video, 'generating_script', 25, 'Menyusun naskah 30 detik dengan Google Gemini...');

                $scriptResult = $gemini->generateScript(
                    topicTitle: $video->topic_name ?: ($video->topic ? $video->topic->title : 'Filosofi Hindu Bali'),
                    targetAudience: $video->target_audience ?: 'Anak Muda',
                    tone: $video->tone ?: 'Inspiratif & Hangat',
                    customIdea: $video->summary_text,
                    sources: $sources
                );

                $data = $scriptResult['data'] ?? [];

                $video->update([
                    'title' => $data['title'] ?? $video->title,
                    'hook_text' => $data['hook_text'] ?? '',
                    'reflection_text' => $data['reflection_text'] ?? '',
                    'summary_text' => $data['summary_text'] ?? $video->summary_text,
                ]);

                // Create storyboard scenes
                $scenesData = $data['scenes'] ?? [];
                foreach ($scenesData as $idx => $s) {
                    StoryboardScene::create([
                        'video_id' => $video->id,
                        'scene_order' => $s['scene_order'] ?? ($idx + 1),
                        'start_time' => $s['start_time'] ?? ($idx * 6.0),
                        'end_time' => $s['end_time'] ?? (($idx + 1) * 6.0),
                        'duration' => $s['duration'] ?? 6.0,
                        'narration_text' => $s['narration_text'] ?? '',
                        'onscreen_text' => $s['onscreen_text'] ?? '',
                        'visual_prompt' => $s['visual_prompt'] ?? '',
                        'camera_motion' => $s['camera_motion'] ?? 'zoom_in',
                        'transition_type' => $s['transition_type'] ?? 'fade',
                        'source_citation' => $s['source_citation'] ?? 'Kearifan Hindu Bali',
                        'status' => 'pending',
                    ]);
                }
            }

            // Check if user requested to review script before rendering
            if ($this->reviewScriptFirst) {
                $this->updateStatus($video, 'waiting_script_approval', 35, 'Naskah siap ditinjau sebelum produksi media.');
                return;
            }

            // Stage 3: Generate Audio Narasi
            $this->updateStatus($video, 'generating_narration', 40, 'Memproduksi narasi suara bahasa Indonesia...');
            $scenes = $video->scenes()->orderBy('scene_order')->get();

            foreach ($scenes as $scene) {
                if (empty($scene->audio_path)) {
                    $audioRes = $gemini->generateSceneAudio($scene->narration_text, $video->voice_id ?: 'Pari');
                    $scene->update(['audio_path' => $audioRes['audio_path']]);
                }
            }

            // Stage 4: Generate Visual Adegan
            $this->updateStatus($video, 'generating_visuals', 50, 'Membuat ilustrasi visual dengan model gambar Gemini...');
            foreach ($scenes as $scene) {
                if (empty($scene->image_path)) {
                    $imgRes = $gemini->generateSceneVisual(
                        $scene->visual_prompt ?: 'Balinese Hindu philosophical art',
                        $video->visual_style ?: 'modern_editorial',
                        $video->aspect_ratio ?: '9:16'
                    );
                    $scene->update([
                        'image_path' => $imgRes['image_path'],
                        'status' => 'ready',
                    ]);
                }
            }

            // Stage 5: Render Video 30 Detik dengan FFmpeg
            $renderer->renderVideo($video, function (int $progress, string $label) use ($video) {
                $this->updateStatus($video, 'rendering', $progress, $label);
            });

            // Stage 6: Generate Quiz interaktif
            try {
                if ($video->quizzes()->count() === 0) {
                    $quizzes = $gemini->generateQuiz($video->title, $video->transcript_text ?: $video->hook_text);
                    foreach ($quizzes as $q) {
                        VideoQuiz::create([
                            'video_id' => $video->id,
                            'question' => $q['question'],
                            'options' => $q['options'],
                            'correct_option_index' => $q['correct_option_index'],
                            'explanation' => $q['explanation'],
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Quiz generation skipped: " . $e->getMessage());
            }

            // Deduct user quota on successful generation
            $user = $video->user;
            if ($user && $user->quota_used < $user->quota_total) {
                $user->increment('quota_used');
            }

            $this->updateStatus($video, 'completed', 100, 'Video 30 detik siap ditonton dan diunduh!');

        } catch (\Throwable $e) {
            Log::error("ProduceVideoJob failed for video {$video->id}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            $friendlyMessage = $this->formatErrorMessage($e->getMessage());

            $video->update([
                'status' => 'failed',
                'error_message' => $friendlyMessage,
                'current_stage_label' => 'Terjadi kendala saat memproduksi video.',
            ]);

            event(new VideoStatusUpdated($video, $video->progress_percent, 'Terjadi kendala saat memproduksi video.', 'failed'));

            throw $e;
        }
    }

    protected function updateStatus(Video $video, string $status, int $percent, string $label): void
    {
        $video->update([
            'status' => $status,
            'progress_percent' => $percent,
            'current_stage_label' => $label,
        ]);

        try {
            event(new VideoStatusUpdated($video, $percent, $label, $status));
        } catch (\Throwable $e) {
            // Silently ignore broadcasting errors if reverb server is not active
        }
    }

    protected function formatErrorMessage(string $raw): string
    {
        if (str_contains($raw, 'API_KEY_INVALID') || str_contains($raw, 'KEY_MISSING')) {
            return 'API Key Gemini belum valid atau belum diisi pada Pengaturan Admin.';
        }
        if (str_contains($raw, 'QUOTA_EXCEEDED') || str_contains($raw, 'RESOURCE_EXHAUSTED')) {
            return 'Kuota permintaan Google Gemini API telah mencapai batas maksimal.';
        }
        if (str_contains($raw, 'FFmpeg')) {
            return 'Kendala rendering media: ' . substr($raw, 0, 200);
        }
        return 'Gagal memproses video: ' . substr($raw, 0, 250);
    }
}
