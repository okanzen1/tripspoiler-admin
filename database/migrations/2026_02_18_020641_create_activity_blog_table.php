<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_blog', function (Blueprint $table) {
            $table->id();

            $table->foreignId('blog_id')
                  ->constrained()
                  ->onDelete('cascade');

            $table->foreignId('activity_id')
                  ->constrained()
                  ->onDelete('cascade');

            $table->timestamps();

            $table->unique(['blog_id', 'activity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_blog');
    }
};