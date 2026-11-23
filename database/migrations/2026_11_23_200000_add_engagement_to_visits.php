<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sayfada kalma süresi, kaydırma ve tıklanan yerler (sıcaklık haritası). Site yazar (tripspoiler: track.js → /t/leave).
 *  visits.view_key  tarayıcının o sayfa açılışı için ürettiği rastgele kimlik (süre/tıklama sonradan bununla eklenir)
 *  visits.duration  sayfada aktif kalınan saniye (sekme arka plandayken sayılmaz)
 *  visits.scroll    sayfanın en fazla yüzde kaçına kadar inildi
 *  visits.viewport_h ekran boyu (ilk ekran çizgisi)
 *  page_clicks      sayfadaki her tıklama: konum (x, y piksel; ekran genişliği vw), tıklanan şeyin yazısı ve bağlantısı
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->string('view_key', 24)->nullable()->unique();
            $table->unsignedInteger('duration')->nullable();
            $table->unsignedTinyInteger('scroll')->nullable();
            $table->unsignedSmallInteger('viewport_h')->nullable(); // ekranın boyu: "ilk ekranda görünen" çizgisi
        });

        Schema::create('page_clicks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('visit_id')->index();
            $table->string('path', 500);
            $table->char('path_hash', 40);
            $table->string('device', 10)->nullable();
            $table->unsignedSmallInteger('x');
            $table->unsignedInteger('y');
            $table->unsignedSmallInteger('vw');
            $table->unsignedInteger('ph')->nullable();
            $table->string('label', 120)->nullable();
            $table->string('href', 500)->nullable();
            $table->unsignedInteger('at')->nullable();
            $table->boolean('rage')->default(false); // aynı yere art arda (sinirli) tıklama: "çalışmıyor" sandı
            $table->boolean('dead')->default(false); // tıklanamayan bir yere tıklama: bağlantı sandı
            $table->timestamp('created_at')->index();

            $table->index(['path_hash', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_clicks');
        Schema::table('visits', function (Blueprint $table) {
            $table->dropUnique(['view_key']);
            $table->dropColumn(['view_key', 'duration', 'scroll', 'viewport_h']);
        });
    }
};
