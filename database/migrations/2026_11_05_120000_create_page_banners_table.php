<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sayfa banner resimleri (Aktiviteler, Şehirler): sayfanın genel resmi (city_id boş) ve şehre özel resimler;
 * her biri için isteğe bağlı telefon resmi. Önceki cities.hero_image ve Setting "activities_hero_image" buraya taşınır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_banners', function (Blueprint $table) {
            $table->id();
            $table->string('page_key', 50);
            $table->foreignId('city_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('image')->nullable();
            $table->string('image_mobile')->nullable();
            $table->timestamps();
            $table->unique(['page_key', 'city_id']);
        });

        $now = now();
        if ($general = DB::table('settings')->where('key', 'activities_hero_image')->value('value')) {
            DB::table('page_banners')->insert(['page_key' => 'activities', 'city_id' => null, 'image' => $general, 'created_at' => $now, 'updated_at' => $now]);
        }
        foreach (DB::table('cities')->whereNotNull('hero_image')->pluck('hero_image', 'id') as $cityId => $path) {
            DB::table('page_banners')->insert(['page_key' => 'activities', 'city_id' => $cityId, 'image' => $path, 'created_at' => $now, 'updated_at' => $now]);
        }
        DB::table('settings')->where('key', 'activities_hero_image')->delete();

        Schema::table('cities', fn (Blueprint $table) => $table->dropColumn('hero_image'));
    }

    public function down(): void
    {
        Schema::table('cities', fn (Blueprint $table) => $table->string('hero_image')->nullable()->after('longitude'));
        Schema::dropIfExists('page_banners');
    }
};
