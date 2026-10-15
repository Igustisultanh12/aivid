<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Video extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'series_id',
        'series_episode',
        'title',
        'slug',
        'hook_text',
        'reflection_text',
        'summary_text',
        'topic_id',
        'topic_name',
        'target_audience',
        'tone',
        'production_mode',
        'visual_style',
        'aspect_ratio',
        'voice_id',
        'background_music_id',
        'has_subtitles',
        'status',
        'progress_percent',
        'current_stage_label',
        'error_message',
        'duration_seconds',
        'video_path',
        'thumbnail_path',
        'subtitles_srt_path',
        'subtitles_vtt_path',
        'transcript_text',
        'is_public',
        'is_featured',
        'moderation_status',
        'views_count',
        'downloads_count',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'is_featured' => 'boolean',
            'has_subtitles' => 'boolean',
            'progress_percent' => 'integer',
            'duration_seconds' => 'float',
            'views_count' => 'integer',
            'downloads_count' => 'integer',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($video) {
            if (empty($video->slug)) {
                $video->slug = Str::slug($video->title) . '-' . Str::random(6);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(VideoSeries::class, 'series_id');
    }

    public function scenes(): HasMany
    {
        return $this->hasMany(StoryboardScene::class)->orderBy('scene_order');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(VideoQuiz::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(VideoBookmark::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(CommunityReport::class);
    }

    public function aiLogs(): HasMany
    {
        return $this->hasMany(AiUsageLog::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
