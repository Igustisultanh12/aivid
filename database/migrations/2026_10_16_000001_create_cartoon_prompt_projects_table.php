<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cartoon_prompt_projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('theme'); // fable, dharma_kids, comedy, fantasy, anime_chibi, sci_fi, custom
            $table->string('custom_theme_title')->nullable();
            $table->text('story_premise')->nullable();
            $table->unsignedInteger('target_duration')->default(30); // 30 or 60 seconds
            $table->string('visual_style')->default('pixar_3d');
            $table->string('target_engine')->default('universal');
            $table->string('aspect_ratio')->default('9:16');
            $table->text('character_description')->nullable();
            $table->unsignedInteger('scene_count')->default(6);
            $table->decimal('total_duration', 6, 2)->default(30.00);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('cartoon_prompt_scenes', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('cartoon_prompt_project_id')
                ->constrained('cartoon_prompt_projects')
                ->cascadeOnDelete();
            $table->unsignedInteger('scene_number');
            $table->decimal('start_second', 5, 2)->default(0.00);
            $table->decimal('end_second', 5, 2)->default(5.00);
            $table->decimal('duration', 5, 2)->default(5.00);
            $table->string('title');
            $table->text('action_description');
            $table->text('visual_prompt');
            $table->string('camera_movement')->nullable();
            $table->text('dialogue_or_narration')->nullable();
            $table->string('sound_effects')->nullable();
            $table->string('music_mood')->nullable();
            $table->text('negative_prompt')->nullable();
            $table->timestamps();

            $table->index(['cartoon_prompt_project_id', 'scene_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cartoon_prompt_scenes');
        Schema::dropIfExists('cartoon_prompt_projects');
    }
};
