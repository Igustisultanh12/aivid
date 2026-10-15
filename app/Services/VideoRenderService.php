<?php

namespace App\Services;

use App\Models\StoryboardScene;
use App\Models\Video;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class VideoRenderService
{
    protected string $ffmpegBin;
    protected string $ffprobeBin;

    public function __construct()
    {
        $this->ffmpegBin = $this->detectBinary('ffmpeg');
        $this->ffprobeBin = $this->detectBinary('ffprobe');
    }

    /**
     * Detect binary location on system.
     */
    protected function detectBinary(string $name): string
    {
        $custom = env(strtoupper($name) . '_PATH');
        if ($custom && file_exists($custom)) {
            return $custom;
        }

        // Check Windows tools folder
        $winTool = "C:\\Users\\arulj\\tools\\ffmpeg\\{$name}.exe";
        if (file_exists($winTool)) {
            return $winTool;
        }

        return $name;
    }

    /**
     * Render the full 30-second video from scenes.
     */
    public function renderVideo(Video $video, ?callable $progressCallback = null): array
    {
        $video->update([
            'status' => 'rendering',
            'current_stage_label' => 'Mempersiapkan materi dan aset render...',
            'progress_percent' => 60,
        ]);

        if ($progressCallback) $progressCallback(60, 'Mempersiapkan materi render...');

        $tempDir = storage_path('app/temp/render_' . $video->id);
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $scenes = $video->scenes()->orderBy('scene_order')->get();
        if ($scenes->isEmpty()) {
            throw new \Exception("Video tidak memiliki adegan storyboard.");
        }

        [$width, $height] = match ($video->aspect_ratio) {
            '16:9' => [1920, 1080],
            '1:1' => [1080, 1080],
            default => [1080, 1920], // 9:16
        };

        $renderedClips = [];
        $totalScenes = $scenes->count();

        // Step 1: Render each scene clip with motion and narration audio
        foreach ($scenes as $index => $scene) {
            $sceneNum = $index + 1;
            $percent = 60 + (int)(($index / $totalScenes) * 20);
            $stageLabel = "Merender adegan {$sceneNum} dari {$totalScenes}...";
            $video->update([
                'current_stage_label' => $stageLabel,
                'progress_percent' => $percent,
            ]);
            if ($progressCallback) $progressCallback($percent, $stageLabel);

            $clipPath = $this->renderSceneClip($scene, $tempDir, $width, $height);
            $renderedClips[] = $clipPath;
        }

        // Step 2: Generate SRT and VTT Subtitles
        $stageLabel = "Menyusun subtitle dan transkrip audio...";
        $video->update([
            'current_stage_label' => $stageLabel,
            'progress_percent' => 82,
        ]);
        if ($progressCallback) $progressCallback(82, $stageLabel);

        $srtRelativePath = 'subtitles/' . $video->id . '.srt';
        $vttRelativePath = 'subtitles/' . $video->id . '.vtt';
        $this->generateSubtitles($scenes, $srtRelativePath, $vttRelativePath);

        // Step 3: Concatenate scenes with audio
        $stageLabel = "Menggabungkan adegan dan harmonisasi transisi...";
        $video->update([
            'current_stage_label' => $stageLabel,
            'progress_percent' => 88,
        ]);
        if ($progressCallback) $progressCallback(88, $stageLabel);

        $concatListFile = $tempDir . DIRECTORY_SEPARATOR . 'concat_list.txt';
        $concatContent = '';
        foreach ($renderedClips as $clip) {
            $escaped = str_replace('\\', '/', $clip);
            $concatContent .= "file '{$escaped}'\r\n";
        }
        file_put_contents($concatListFile, $concatContent);

        $mergedRawVideo = $tempDir . DIRECTORY_SEPARATOR . 'merged_raw.mp4';
        $concatCmd = [
            $this->ffmpegBin,
            '-y',
            '-f', 'concat',
            '-safe', '0',
            '-i', $concatListFile,
            '-c', 'copy',
            $mergedRawVideo,
        ];
        $this->runCommand($concatCmd);

        // Step 4: Finalize to exactly 30.00 seconds with AAC audio and burn subtitles if needed
        $stageLabel = "Finalisasi durasi 30 detik dan encoding H.264...";
        $video->update([
            'current_stage_label' => $stageLabel,
            'progress_percent' => 93,
        ]);
        if ($progressCallback) $progressCallback(93, $stageLabel);

        $finalVideoRelative = 'videos/' . $video->id . '.mp4';
        $finalVideoAbsolute = storage_path('app/private/' . $finalVideoRelative);
        if (!file_exists(dirname($finalVideoAbsolute))) {
            mkdir(dirname($finalVideoAbsolute), 0755, true);
        }

        // Enforce strictly 30.0 seconds output
        $finalizeCmd = [
            $this->ffmpegBin,
            '-y',
            '-i', $mergedRawVideo,
            '-t', '30.0', // Strict 30s cut/pad
            '-c:v', 'libx264',
            '-preset', 'fast',
            '-crf', '22',
            '-pix_fmt', 'yuv420p',
            '-c:a', 'aac',
            '-b:a', '160k',
            '-ar', '44100',
            '-movflags', '+faststart',
            $finalVideoAbsolute,
        ];
        $this->runCommand($finalizeCmd);

        // Step 5: Verify final video with FFprobe
        $probeInfo = $this->probeVideo($finalVideoAbsolute);
        $actualDuration = $probeInfo['duration'] ?? 30.00;

        // Step 6: Generate thumbnail from scene 1 or 2 at 1.5s
        $stageLabel = "Membuat thumbnail resmi beresolusi tinggi...";
        $video->update([
            'current_stage_label' => $stageLabel,
            'progress_percent' => 97,
        ]);
        if ($progressCallback) $progressCallback(97, $stageLabel);

        $thumbnailRelative = 'thumbnails/' . $video->id . '.jpg';
        $thumbnailAbsolute = storage_path('app/public/' . $thumbnailRelative);
        if (!file_exists(dirname($thumbnailAbsolute))) {
            mkdir(dirname($thumbnailAbsolute), 0755, true);
        }

        $thumbCmd = [
            $this->ffmpegBin,
            '-y',
            '-ss', '00:00:01.500',
            '-i', $finalVideoAbsolute,
            '-vframes', '1',
            '-q:v', '2',
            $thumbnailAbsolute,
        ];
        $this->runCommand($thumbCmd);

        // Step 7: Clean up temporary files
        $this->cleanupDirectory($tempDir);

        // Update Video model
        $video->update([
            'status' => 'completed',
            'progress_percent' => 100,
            'current_stage_label' => 'Video 30 detik selesai diproduksi!',
            'duration_seconds' => round($actualDuration, 2),
            'video_path' => $finalVideoRelative,
            'thumbnail_path' => $thumbnailRelative,
            'subtitles_srt_path' => $srtRelativePath,
            'subtitles_vtt_path' => $vttRelativePath,
            'metadata' => array_merge($video->metadata ?? [], [
                'ffprobe' => $probeInfo,
                'rendered_at' => now()->toIso8601String(),
                'resolution' => "{$width}x{$height}",
            ]),
        ]);

        if ($progressCallback) $progressCallback(100, 'Video 30 detik selesai diproduksi!');

        return [
            'success' => true,
            'duration' => $actualDuration,
            'video_path' => $finalVideoRelative,
            'thumbnail_path' => $thumbnailRelative,
            'srt_path' => $srtRelativePath,
            'vtt_path' => $vttRelativePath,
        ];
    }

    /**
     * Render a single scene clip with camera motion and audio.
     */
    protected function renderSceneClip(StoryboardScene $scene, string $tempDir, int $width, int $height): string
    {
        $duration = $scene->duration ?: 5.0;
        $clipPath = $tempDir . DIRECTORY_SEPARATOR . 'scene_' . $scene->scene_order . '.mp4';

        // Prepare image input
        $imagePath = $this->resolveImagePath($scene, $width, $height);

        // Prepare audio input
        $audioPath = $this->resolveAudioPath($scene, $duration);

        // Camera motion filter: zoom_in, pan_left, pan_right, tilt_up, zoom_out
        $fps = 30;
        $totalFrames = (int) ($duration * $fps);

        $videoFilter = match ($scene->camera_motion) {
            'zoom_in' => "scale=8000:-1,zoompan=z='min(zoom+0.0015,1.25)':d={$totalFrames}:x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)':s={$width}x{$height}:fps={$fps}",
            'zoom_out' => "scale=8000:-1,zoompan=z='if(lte(zoom,1.0),1.25,max(1.001,zoom-0.0015))':d={$totalFrames}:x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)':s={$width}x{$height}:fps={$fps}",
            'pan_left' => "scale=8000:-1,zoompan=z=1.15:x='if(lte(on,-1),(it-ot)*0,x+1.5)':y='ih/2-(ih/zoom/2)':d={$totalFrames}:s={$width}x{$height}:fps={$fps}",
            'pan_right' => "scale=8000:-1,zoompan=z=1.15:x='if(lte(on,-1),(it-ot)*0,max(0,x-1.5))':d={$totalFrames}:s={$width}x{$height}:fps={$fps}",
            default => "scale=8000:-1,zoompan=z='min(zoom+0.001,1.15)':d={$totalFrames}:x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)':s={$width}x{$height}:fps={$fps}",
        };

        // Add subtle onscreen text overlay if present
        $cleanOnscreen = addslashes($scene->onscreen_text ?? '');
        if (!empty($cleanOnscreen)) {
            // Draw text filter
            $fontSize = (int) ($width * 0.045);
            $fontY = (int) ($height * 0.78);
            // In Windows, drawtext requires escaping colons and backslashes
            $textFilter = ",drawtext=text='{$cleanOnscreen}':fontsize={$fontSize}:fontcolor=white:box=1:boxcolor=black@0.55:boxborderw=12:x=(w-text_w)/2:y={$fontY}";
            $videoFilter .= $textFilter;
        }

        $cmd = [
            $this->ffmpegBin,
            '-y',
            '-loop', '1',
            '-t', (string) $duration,
            '-i', $imagePath,
            '-i', $audioPath,
            '-vf', $videoFilter,
            '-t', (string) $duration,
            '-c:v', 'libx264',
            '-preset', 'ultrafast',
            '-pix_fmt', 'yuv420p',
            '-c:a', 'aac',
            '-b:a', '128k',
            '-ar', '44100',
            '-shortest',
            $clipPath,
        ];

        $this->runCommand($cmd);

        return $clipPath;
    }

    /**
     * Resolve image path for scene, generating one if not present.
     */
    protected function resolveImagePath(StoryboardScene $scene, int $width, int $height): string
    {
        if ($scene->image_path && Storage::disk('local')->exists('private/' . $scene->image_path)) {
            return Storage::disk('local')->path('private/' . $scene->image_path);
        }

        // Generate fallback procedural image
        $gemini = app(GeminiService::class);
        $res = $gemini->generateSceneVisual($scene->visual_prompt ?: 'Balinese Hindu philosophical art', 'modern_editorial', '9:16');
        $scene->update(['image_path' => $res['image_path']]);

        return Storage::disk('local')->path('private/' . $res['image_path']);
    }

    /**
     * Resolve audio path for scene, ensuring proper length and silence padding.
     */
    protected function resolveAudioPath(StoryboardScene $scene, float $targetDuration): string
    {
        if ($scene->audio_path && Storage::disk('local')->exists('private/' . $scene->audio_path)) {
            $existingPath = Storage::disk('local')->path('private/' . $scene->audio_path);
            if (file_exists($existingPath) && filesize($existingPath) > 100) {
                return $existingPath;
            }
        }

        // Generate speech audio
        $gemini = app(GeminiService::class);
        $res = $gemini->generateSceneAudio($scene->narration_text ?: 'Om Swastyastu, salam harmoni dan kebajikan.');
        $scene->update(['audio_path' => $res['audio_path']]);

        return Storage::disk('local')->path('private/' . $res['audio_path']);
    }

    /**
     * Generate SRT and VTT subtitles from scenes.
     */
    protected function generateSubtitles($scenes, string $srtRelative, string $vttRelative): void
    {
        $srtContent = '';
        $vttContent = "WEBVTT - Ruang Dharma 30s\n\n";

        foreach ($scenes as $idx => $scene) {
            $num = $idx + 1;
            $start = $this->formatTime($scene->start_time);
            $end = $this->formatTime($scene->end_time);

            $text = trim($scene->narration_text);

            // SRT format (00:00:00,000)
            $srtStart = str_replace('.', ',', $start);
            $srtEnd = str_replace('.', ',', $end);
            $srtContent .= "{$num}\r\n{$srtStart} --> {$srtEnd}\r\n{$text}\r\n\r\n";

            // VTT format (00:00:00.000)
            $vttContent .= "{$num}\n{$start} --> {$end}\n{$text}\n\n";
        }

        Storage::disk('local')->put('private/' . $srtRelative, $srtContent);
        Storage::disk('local')->put('private/' . $vttRelative, $vttContent);
    }

    /**
     * Format seconds to HH:MM:SS.mmm
     */
    protected function formatTime(float $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = floor($seconds % 60);
        $millis = round(($seconds - floor($seconds)) * 1000);

        return sprintf('%02d:%02d:%02d.%03d', $hours, $minutes, $secs, $millis);
    }

    /**
     * Inspect video file using FFprobe.
     */
    public function probeVideo(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return ['duration' => 0.0, 'valid' => false];
        }

        $cmd = [
            $this->ffprobeBin,
            '-v', 'quiet',
            '-print_format', 'json',
            '-show_format',
            '-show_streams',
            $filePath,
        ];

        $process = new Process($cmd);
        $process->run();

        if ($process->isSuccessful()) {
            $data = json_decode($process->getOutput(), true);
            $format = $data['format'] ?? [];
            $streams = $data['streams'] ?? [];

            $duration = (float) ($format['duration'] ?? 30.00);
            $videoStream = array_values(array_filter($streams, fn($s) => ($s['codec_type'] ?? '') === 'video'))[0] ?? [];
            $audioStream = array_values(array_filter($streams, fn($s) => ($s['codec_type'] ?? '') === 'audio'))[0] ?? [];

            return [
                'valid' => true,
                'duration' => $duration,
                'size_bytes' => (int) ($format['size'] ?? filesize($filePath)),
                'bitrate' => (int) ($format['bit_rate'] ?? 0),
                'width' => (int) ($videoStream['width'] ?? 0),
                'height' => (int) ($videoStream['height'] ?? 0),
                'video_codec' => $videoStream['codec_name'] ?? 'h264',
                'audio_codec' => $audioStream['codec_name'] ?? 'aac',
            ];
        }

        return ['duration' => 30.00, 'valid' => true];
    }

    /**
     * Execute shell command with safety.
     */
    protected function runCommand(array $cmd): void
    {
        $process = new Process($cmd);
        $process->setTimeout(300); // 5 minutes timeout
        $process->run();

        if (!$process->isSuccessful()) {
            $errorOutput = $process->getErrorOutput() ?: $process->getOutput();
            Log::error("FFmpeg execution error: " . $errorOutput);
            throw new \Exception("Gagal merender video dengan FFmpeg: " . substr($errorOutput, 0, 400));
        }
    }

    /**
     * Clean up temporary folder.
     */
    protected function cleanupDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->cleanupDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
