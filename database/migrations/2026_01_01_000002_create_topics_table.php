<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description');
            $table->string('category'); // Tradisi Bali, Filosofi Umum, Etika & Karma, Simbolisme, dsb
            $table->string('cultural_context')->default('Tradisi Hindu Bali');
            $table->boolean('is_popular')->default(false);
            $table->json('suggested_prompts')->nullable();
            $table->string('icon_name')->default('Sparkles');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
