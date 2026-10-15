<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class AppSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'is_encrypted',
        'group',
    ];

    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
        ];
    }

    /**
     * Get a setting by key, automatically decrypting if encrypted.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        if (!$setting || $setting->value === null) {
            return $default;
        }

        if ($setting->is_encrypted) {
            try {
                return Crypt::decryptString($setting->value);
            } catch (\Throwable $e) {
                Log::error("Failed to decrypt setting [{$key}]: " . $e->getMessage());
                return $default;
            }
        }

        return $setting->value;
    }

    /**
     * Set a setting by key, encrypting if specified.
     */
    public static function set(string $key, mixed $value, bool $isEncrypted = false, string $group = 'general'): self
    {
        $storedValue = $value;
        if ($value !== null && $isEncrypted) {
            $storedValue = Crypt::encryptString((string) $value);
        }

        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'is_encrypted' => $isEncrypted,
                'group' => $group,
            ]
        );
    }

    /**
     * Check if a setting is configured and not empty.
     */
    public static function has(string $key): bool
    {
        $setting = static::where('key', $key)->first();
        return $setting && !empty($setting->value);
    }

    /**
     * Return masked view of a sensitive key (e.g. AIzaSy...****).
     */
    public static function getMasked(string $key): ?string
    {
        $val = static::get($key);
        if (!$val) {
            return null;
        }

        $length = strlen($val);
        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        $prefix = substr($val, 0, 6);
        $suffix = substr($val, -4);
        return $prefix . str_repeat('*', min(12, $length - 10)) . $suffix;
    }
}
