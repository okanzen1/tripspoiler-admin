<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Blog türü: "guide" (şehir, müze, aktivite, gezi rehberi) ya da "news" (güncel seyahat haberi: vize, ulaşım, etkinlik).
 * Sitede iki ayrı akış: Destination Guides (şehre göre) ve Travel News (şehirden bağımsız).
 * Mevcut yazılar: bir şehre bağlı olan rehber, hiçbir şehre bağlı olmayan haber (panelden değiştirilebilir).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->string('kind', 10)->default('guide')->after('city_id')->index();
        });

        DB::table('blogs')->whereNull('city_id')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('blog_city')->whereColumn('blog_city.blog_id', 'blogs.id'))
            ->update(['kind' => 'news']);
    }

    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
};
