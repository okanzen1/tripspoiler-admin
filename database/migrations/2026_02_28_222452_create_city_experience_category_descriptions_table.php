<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('city_experience_category_descriptions', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('city_experience_category_id');

            $table->foreign(
                'city_experience_category_id',
                'cec_desc_fk'
            )
                ->references('id')
                ->on('city_experience_categories')
                ->cascadeOnDelete();

            // JSON olarak saklayacağız
            $table->json('description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('city_experience_category_descriptions');
    }
};
