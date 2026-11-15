<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partner bağlantıları: aktivite listesinin sonundaki "Aradığını bulamadın mı?" kartları.
 * Detay sayfası yok; tıklayınca doğrudan partnerin şehir sayfasına gider (ör. Megapass'in İstanbul aktiviteleri).
 * city_id boş = bütün şehirlerde görünür.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->string('url', 1000);
            $table->string('count_label', 20)->nullable(); // "200+" gibi; boşsa sayı yazılmaz
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_links');
    }
};
