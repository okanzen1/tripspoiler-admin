<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();

            $table->json('title');
            $table->json('slug')->unique();
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();

            $table->foreignId('city_id')
                ->nullable()
                ->constrained('cities')
                ->nullOnDelete();

            $table->string('place')->nullable();
            $table->unsignedBigInteger('place_id')->nullable();

            $table->unsignedBigInteger('click_count')->default(0);

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(false);

            $table->timestamps();

            $table->index(['city_id']);
            $table->index(['place', 'place_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};
