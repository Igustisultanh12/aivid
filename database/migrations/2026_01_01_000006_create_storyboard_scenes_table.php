<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storyboard_scenes', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('video_id')->constrained('videos')->cascadeOnDelete();
            $table->integer('scene_order'); // 1 to 6
            $table->decimal('start_time', 5, 2)->default(0.00);
            $table->decimal('end_time', 5, 2)->default(0.00);
            $table->decimal('duration', 5, 2)->default(5.00);
            $table->text('narration_text');
            $table->string('onscreen_text')->nullable();
            $table->text('visual_prompt');
            $table->string('camera_motion')->default('zoom_in'); // zoom_in, pan_left, pan_right, zoom_out, tilt_up
            $table->string('transition_type')->default('fade'); // fade, cut, wipe
            $table->foreignId('source_id')->nullable()->constrained('sources')->nullOnDelete();
            $table->string('source_citation')->nullable();
            $table->string('image_path')->nullable();
            $table->string('video_clip_path')->nullable();
            $table->string('audio_path')->nullable();
            $table->decimal('actual_audio_duration', 5, 2)->nullable();
            $table->string('status')->default('pending'); // pending, generating, ready, failed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storyboard_scenes');
    }
};
