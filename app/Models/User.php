<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'quota_total',
        'quota_used',
        'avatar_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'quota_total' => 'integer',
            'quota_used' => 'integer',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasRemainingQuota(): bool
    {
        return $this->quota_used < $this->quota_total;
    }

    public function remainingQuota(): int
    {
        return max(0, $this->quota_total - $this->quota_used);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    public function series(): HasMany
    {
        return $this->hasMany(VideoSeries::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(VideoBookmark::class);
    }

    public function scheduledGenerations(): HasMany
    {
        return $this->hasMany(ScheduledGeneration::class);
    }
}
