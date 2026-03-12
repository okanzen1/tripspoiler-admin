<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {

            $table->id();

            $table->text('name');
            $table->text('email')->nullable();

            $table->string('source');
            $table->unsignedBigInteger('source_id')->nullable();

            $table->tinyInteger('rating')->default(5);
            $table->text('comment')->nullable();

            $table->boolean('approved')->default(true);

            $table->timestamps();

            $table->index(['source', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
