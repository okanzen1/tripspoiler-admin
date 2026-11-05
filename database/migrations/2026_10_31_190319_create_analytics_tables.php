<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Analitik tabloları. Front (tripspoiler) bu tablolara yazar, admin okur.
 *
 * visitor_id: IP + user agent + günlük tuzdan üretilen hash (KVKK için ham IP saklanmaz).
 * session_id: 30 dk hareketsizlikte yenilenen oturum kimliği.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Her sayfa görüntüleme bir satır
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_id', 64)->index();
            $table->string('session_id', 64);
            $table->string('path', 500);
            $table->string('page_type', 30)->nullable();     // home, city, activity, blog, other
            $table->unsignedBigInteger('page_id')->nullable();
            $table->string('referrer_host', 190)->nullable();
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->char('country', 2)->nullable();
            $table->string('device', 10)->nullable();        // desktop, mobile, tablet
            $table->string('locale', 5)->nullable();
            $table->timestamp('created_at');

            $table->index(['page_type', 'page_id']);
            // Dönem sorguları (tekil ziyaretçi, sayfa, oturum) tabloya inmeden indeksten cevaplanır
            $table->index(['created_at', 'visitor_id']);
            $table->index(['created_at', 'session_id']);
        });

        // Affiliate linkine her tıklama bir satır
        Schema::create('affiliate_clicks', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_id', 64)->index();
            $table->string('session_id', 64);
            $table->string('partner', 50)->index();          // getyourguide, viator, tiqets...
            $table->unsignedBigInteger('activity_id')->nullable()->index();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('path', 500);                     // tıklamanın yapıldığı sayfa
            $table->string('page_type', 30)->nullable();
            $table->text('target_url');
            $table->char('country', 2)->nullable();
            $table->string('device', 10)->nullable();
            $table->string('locale', 5)->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_clicks');
        Schema::dropIfExists('visits');
    }
};
