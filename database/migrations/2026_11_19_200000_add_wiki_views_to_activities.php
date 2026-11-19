<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Popülerlik: aktivitenin bağlı olduğu Wikipedia sayfası ve o sayfanın son 30 gündeki okunma sayısı
 * (ücretsiz Wikimedia verisi, her gün güncellenir). Site her şehrin en ilgi gören ürününü bununla seçer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->string('wiki_title', 190)->nullable()->after('is_popular');
            $table->unsignedInteger('wiki_views')->nullable()->after('wiki_title');
            $table->timestamp('wiki_views_at')->nullable()->after('wiki_views');
            $table->index('wiki_views');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex(['wiki_views']);
            $table->dropColumn(['wiki_title', 'wiki_views', 'wiki_views_at']);
        });
    }
};
