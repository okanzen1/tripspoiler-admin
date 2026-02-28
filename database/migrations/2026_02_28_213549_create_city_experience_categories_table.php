<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('city_experience_categories', function (Blueprint $table) {
            $table->id();

            // Page content'a bağlı
            $table->foreignId('page_content_id')
                ->constrained('page_contents')
                ->cascadeOnDelete();

            // Spatie translatable için JSON
            $table->json('name');

            $table->integer('sort_order')->default(0);
            $table->boolean('status')->default(true);

            $table->timestamps();

            // Performans için index
            $table->index(['page_content_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('city_experience_categories');
    }
};
