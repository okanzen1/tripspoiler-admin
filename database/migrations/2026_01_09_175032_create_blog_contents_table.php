<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_contents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('blog_id')
                ->nullable()
                ->constrained('blogs')
                ->nullOnDelete();
                
            $table->longText('excerpt')->nullable();
            $table->longText('content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_contents');
    }
};
