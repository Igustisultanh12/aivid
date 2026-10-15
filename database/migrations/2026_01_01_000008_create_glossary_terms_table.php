<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('glossary_terms', function (Blueprint $table) {
            $table->id();
            $table->string('term');
            $table->string('sanskrit_or_balinese_origin')->nullable();
            $table->text('meaning');
            $table->text('context')->nullable();
            $table->text('related_traditions')->nullable();
            $table->string('phonetic_pronunciation')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('glossary_terms');
    }
};
