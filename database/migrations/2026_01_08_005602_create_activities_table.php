<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('slug');
            $table->unsignedBigInteger('city_id');
            $table->unsignedBigInteger('country_id');
            $table->unsignedBigInteger('museum_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->foreign('city_id')->references('id')->on('cities');
            $table->foreign('country_id')->references('id')->on('countries');
            $table->foreign('museum_id')->references('id')->on('museums');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
