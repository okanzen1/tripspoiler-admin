<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Blog yazısı birden fazla şehirde yayınlanabilsin: blog_city (yazı ↔ şehir).
 * Hiç şehri olmayan yazı "genel"dir: her şehrin blog listesinde görünür.
 * blogs.city_id ana şehir olarak kalır (seçilen ilk şehir; breadcrumb, sıralama).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_city', function (Blueprint $table) {
            $table->foreignId('blog_id')->constrained()->cascadeOnDelete();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->primary(['blog_id', 'city_id']);
        });

        // mevcut tek şehir bağlantıları taşınır
        DB::table('blog_city')->insertUsing(['blog_id', 'city_id'],
            DB::table('blogs')->whereNotNull('city_id')->select('id', 'city_id'));
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_city');
    }
};
