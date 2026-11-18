<?php

use App\Models\CityGuide;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Şehir sayfasındaki "Kısa bilgiler" tablosu: her bilgi ayrı, çok dilli bir alan (İngilizce yazılır, Çeviriler'den çevrilir)
 * ve bilgilerin en son kontrol edildiği tarih (sitede "Son kontrol" olarak görünür).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('city_guides', function (Blueprint $table) {
            foreach (array_keys(CityGuide::FACTS) as $key) {
                $table->text('fact_' . $key)->nullable();
            }
            $table->date('facts_checked_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('city_guides', function (Blueprint $table) {
            $table->dropColumn([...array_map(fn ($key) => 'fact_' . $key, array_keys(CityGuide::FACTS)), 'facts_checked_at']);
        });
    }
};
