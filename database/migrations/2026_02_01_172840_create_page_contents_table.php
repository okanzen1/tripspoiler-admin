<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_contents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();

            // SEO (JSON)
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();

            // İçerik (JSON)
            $table->json('h1')->nullable();
            $table->json('content')->nullable();
            $table->json('faq')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['page_id', 'city_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_contents');
    }
};
