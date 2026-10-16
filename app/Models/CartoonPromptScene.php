<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartoonPromptScene extends Model
{
    use HasFactory;

    protected $fillable = [
        'cartoon_prompt_project_id',
        'scene_number',
        'start_second',
        'end_second',
        'duration',
        'title',
        'action_description',
        'visual_prompt',
        'camera_movement',
        'dialogue_or_narration',
        'sound_effects',
        'music_mood',
        'negative_prompt',
    ];

    protected $casts = [
        'scene_number' => 'integer',
        'start_second' => 'float',
        'end_second' => 'float',
        'duration' => 'float',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(CartoonPromptProject::class, 'cartoon_prompt_project_id');
    }

    /**
     * Format a clean time string like "00:00 - 00:05".
     */
    public function getFormattedTimestampAttribute(): string
    {
        $startMin = floor($this->start_second / 60);
        $startSec = $this->start_second % 60;
        $endMin = floor($this->end_second / 60);
        $endSec = $this->end_second % 60;

        return sprintf('%02d:%02d - %02d:%02d', $startMin, $startSec, $endMin, $endSec);
    }
}
