<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('video_id')->nullable()->constrained('videos')->nullOnDelete();
            $table->string('request_type'); // text_script, storyboard, image_generation, tts_narration, video_generation, quiz
            $table->string('model_used');
            $table->integer('prompt_tokens')->default(0);
            $table->integer('candidates_tokens')->default(0);
            $table->decimal('estimated_cost_usd', 8, 6)->default(0.000000);
            $table->string('status')->default('success'); // success, failed
            $table->string('error_code')->nullable();
            $table->integer('response_time_ms')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
