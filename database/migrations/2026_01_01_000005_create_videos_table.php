<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('series_id')->nullable()->constrained('video_series')->nullOnDelete();
            $table->integer('series_episode')->nullable();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->text('hook_text')->nullable();
            $table->text('reflection_text')->nullable();
            $table->text('summary_text')->nullable();
            $table->foreignId('topic_id')->nullable()->constrained('topics')->nullOnDelete();
            $table->string('topic_name')->nullable();
            $table->string('target_audience')->default('Anak Muda'); // Anak Muda, Remaja, Umum
            $table->string('tone')->default('Inspiratif & Hangat');
            $table->string('production_mode')->default('illustration_motion'); // illustration_motion, generative_video
            $table->string('visual_style')->default('modern_editorial'); // modern_editorial, balinese_minimalist, vibrant_culture, watercolor_art
            $table->string('aspect_ratio')->default('9:16'); // 9:16, 16:9, 1:1
            $table->string('voice_id')->default('id-ID-Standard-A');
            $table->string('background_music_id')->nullable();
            $table->boolean('has_subtitles')->default(true);
            $table->string('status')->default('draft');
            $table->integer('progress_percent')->default(0);
            $table->string('current_stage_label')->default('Menunggu antrean');
            $table->text('error_message')->nullable();
            $table->decimal('duration_seconds', 5, 2)->default(30.00);
            $table->string('video_path')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->string('subtitles_srt_path')->nullable();
            $table->string('subtitles_vtt_path')->nullable();
            $table->longText('transcript_text')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->string('moderation_status')->default('approved'); // approved, pending, flagged, rejected
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('downloads_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
