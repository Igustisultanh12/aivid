<?php

use App\Services\GeminiService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ganti model Gemini lama yang sudah tidak tersedia (gemini-3.0-flash, imagen-3.0-generate-002,
 * veo-2.0-generate-001, dll.) dengan model resmi terbaru (gemini-3.8-flash, Nano Banana, Veo 3.1).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('app_settings')) {
            return;
        }

        $map = [
            'gemini_text_model' => 'text',
            'gemini_image_model' => 'image',
            'gemini_tts_model' => 'tts',
            'gemini_video_model' => 'video',
        ];

        foreach ($map as $key => $type) {
            $row = DB::table('app_settings')->where('key', $key)->first();
            if (!$row || $row->is_encrypted) {
                continue;
            }

            $resolved = GeminiService::resolveModel($type, $row->value);
            if ($resolved !== $row->value) {
                DB::table('app_settings')->where('key', $key)->update(['value' => $resolved, 'updated_at' => now()]);
            }
        }

        $voice = DB::table('app_settings')->where('key', 'gemini_voice_name')->first();
        if ($voice && !$voice->is_encrypted && strcasecmp((string) $voice->value, 'Pari') === 0) {
            DB::table('app_settings')->where('key', 'gemini_voice_name')->update(['value' => 'Sulafat', 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Model lama sudah dihentikan Google, tidak perlu dikembalikan.
    }
};
