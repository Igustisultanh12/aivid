<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CartoonPromptProject extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'title',
        'theme',
        'custom_theme_title',
        'story_premise',
        'target_duration',
        'visual_style',
        'target_engine',
        'aspect_ratio',
        'character_description',
        'scene_count',
        'total_duration',
        'metadata',
    ];

    protected $casts = [
        'target_duration' => 'integer',
        'scene_count' => 'integer',
        'total_duration' => 'float',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scenes(): HasMany
    {
        return $this->hasMany(CartoonPromptScene::class, 'cartoon_prompt_project_id')
            ->orderBy('scene_number');
    }

    /**
     * Recalculate total duration and sequence timestamps.
     */
    public function recalculateTimeline(): void
    {
        $currentSecond = 0.0;
        $scenes = $this->scenes()->orderBy('scene_number')->get();

        foreach ($scenes as $index => $scene) {
            $duration = (float) ($scene->duration > 0 ? $scene->duration : 5.0);
            $start = $currentSecond;
            $end = round($currentSecond + $duration, 2);

            $scene->updateQuietly([
                'scene_number' => $index + 1,
                'start_second' => $start,
                'end_second' => $end,
                'duration' => $duration,
            ]);

            $currentSecond = $end;
        }

        $this->updateQuietly([
            'scene_count' => $scenes->count(),
            'total_duration' => round($currentSecond, 2),
        ]);
    }
}
