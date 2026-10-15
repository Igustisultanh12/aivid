<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoQuiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_id',
        'question',
        'options',
        'correct_option_index',
        'explanation',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'correct_option_index' => 'integer',
        ];
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }
}
