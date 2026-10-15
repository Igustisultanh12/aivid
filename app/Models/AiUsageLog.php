<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'video_id',
        'request_type',
        'model_used',
        'prompt_tokens',
        'candidates_tokens',
        'estimated_cost_usd',
        'status',
        'error_code',
        'response_time_ms',
    ];

    protected function casts(): array
    {
        return [
            'prompt_tokens' => 'integer',
            'candidates_tokens' => 'integer',
            'estimated_cost_usd' => 'float',
            'response_time_ms' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }
}
