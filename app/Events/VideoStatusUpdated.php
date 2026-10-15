<?php

namespace App\Events;

use App\Models\Video;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VideoStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Video $video,
        public int $progressPercent,
        public string $stageLabel,
        public string $status
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('videos.' . $this->video->id),
            new Channel('users.' . $this->video->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'VideoStatusUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'video_id' => $this->video->id,
            'status' => $this->status,
            'progress_percent' => $this->progressPercent,
            'current_stage_label' => $this->stageLabel,
            'error_message' => $this->video->error_message,
            'duration_seconds' => $this->video->duration_seconds,
            'thumbnail_url' => $this->video->thumbnail_path ? asset('storage/' . $this->video->thumbnail_path) : null,
        ];
    }
}
