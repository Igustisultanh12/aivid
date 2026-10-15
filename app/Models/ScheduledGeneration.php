<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledGeneration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'topic_id',
        'topic_name',
        'custom_idea',
        'target_audience',
        'tone',
        'production_mode',
        'visual_style',
        'aspect_ratio',
        'scheduled_at',
        'timezone',
        'status',
        'executed_video_id',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function executedVideo(): BelongsTo
    {
        return $this->belongsTo(Video::class, 'executed_video_id');
    }
}
