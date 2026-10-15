<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GlossaryTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'term',
        'sanskrit_or_balinese_origin',
        'meaning',
        'context',
        'related_traditions',
        'phonetic_pronunciation',
    ];
}
