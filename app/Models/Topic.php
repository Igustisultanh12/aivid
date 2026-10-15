<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Topic extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'description',
        'category',
        'cultural_context',
        'is_popular',
        'suggested_prompts',
        'icon_name',
    ];

    protected function casts(): array
    {
        return [
            'is_popular' => 'boolean',
            'suggested_prompts' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($topic) {
            if (empty($topic->slug)) {
                $topic->slug = Str::slug($topic->title);
            }
        });
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }
}
