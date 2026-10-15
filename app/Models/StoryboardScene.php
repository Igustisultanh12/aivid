<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoryboardScene extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_id',
        'scene_order',
        'start_time',
        'end_time',
        'duration',
        'narration_text',
        'onscreen_text',
        'visual_prompt',
        'camera_motion',
        'transition_type',
        'source_id',
        'source_citation',
        'image_path',
        'video_clip_path',
        'audio_path',
        'actual_audio_duration',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'scene_order' => 'integer',
            'start_time' => 'float',
            'end_time' => 'float',
            'duration' => 'float',
            'actual_audio_duration' => 'float',
        ];
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }
}
