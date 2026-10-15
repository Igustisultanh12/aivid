<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained('topics')->nullOnDelete();
            $table->string('topic_name')->nullable();
            $table->text('custom_idea')->nullable();
            $table->string('target_audience')->default('Anak Muda');
            $table->string('tone')->default('Inspiratif & Hangat');
            $table->string('production_mode')->default('illustration_motion');
            $table->string('visual_style')->default('modern_editorial');
            $table->string('aspect_ratio')->default('9:16');
            $table->dateTime('scheduled_at');
            $table->string('timezone')->default('Asia/Makassar');
            $table->string('status')->default('scheduled'); // scheduled, running, completed, failed
            $table->foreignUuid('executed_video_id')->nullable()->constrained('videos')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_generations');
    }
};
