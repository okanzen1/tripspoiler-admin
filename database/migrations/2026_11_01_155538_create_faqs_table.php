<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // activity_id / blog_id ikisi de boş = genel SSS (anasayfa / SSS sayfası); doluysa o sayfada
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('blog_id')->nullable(); // blogs tablosu sonra oluşur; yabancı anahtar blog migration'ında
            $table->json('question');
            $table->json('answer');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['activity_id', 'active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
