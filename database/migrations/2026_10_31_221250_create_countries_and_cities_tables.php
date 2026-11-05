<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tüm ISO ülkeleri seed ile gelir; panelden sadece aktif/pasif yapılır
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->char('code', 2)->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('active')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('geoname_id')->nullable()->unique(); // GeoNames'ten gelenler; elle eklenenlerde boş
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('population')->nullable();
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->boolean('active')->default(false)->index();
            $table->timestamps();
            $table->index(['country_id', 'active', 'population']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
        Schema::dropIfExists('countries');
    }
};
