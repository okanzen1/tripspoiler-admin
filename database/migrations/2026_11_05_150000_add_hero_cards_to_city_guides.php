<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Şehir sayfası fotoğrafının üstündeki iki kart: "sol,sağ" bölüm id'leri; boş = otomatik, 0 = gösterme
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('city_guides', fn (Blueprint $table) => $table->string('hero_cards', 40)->nullable()->after('description'));
    }

    public function down(): void
    {
        Schema::table('city_guides', fn (Blueprint $table) => $table->dropColumn('hero_cards'));
    }
};
