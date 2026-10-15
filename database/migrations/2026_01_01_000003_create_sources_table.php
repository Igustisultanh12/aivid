<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('author_or_institution'); // PHDI, Lontar Sundarigama, Bhagavad Gita, dsb
            $table->string('source_type'); // Kitab Suci, Lontar, Panduan Resmi, Kajian Akademis
            $table->text('link_or_document')->nullable();
            $table->string('reference_section')->nullable(); // Bab, Halaman, Sloka
            $table->string('tradition_context'); // Tradisi Hindu Bali vs Filsafat Hindu Umum
            $table->string('region')->default('Bali');
            $table->string('verification_status')->default('verified'); // verified, under_review, rejected
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sources');
    }
};
