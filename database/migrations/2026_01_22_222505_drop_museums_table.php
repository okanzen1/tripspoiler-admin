<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('museums');
    }

    public function down(): void
    {
        Schema::create('museums', function ($table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('city_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
    }
};
