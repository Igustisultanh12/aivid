<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'author_or_institution',
        'source_type',
        'link_or_document',
        'reference_section',
        'tradition_context',
        'region',
        'verification_status',
        'notes',
    ];

    public function storyboardScenes(): HasMany
    {
        return $this->hasMany(StoryboardScene::class);
    }
}
