<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // title, excerpt, content, tags, meta_* çok dilli JSON; panel "en" düzenler
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->json('title');
            $table->string('slug')->unique();
            $table->json('excerpt')->nullable();
            $table->json('content')->nullable();
            $table->json('tags')->nullable();
            $table->string('cover_path')->nullable();
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->boolean('active')->default(false)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        // SSS bir blog yazısına da bağlanabilir (faqs tablosu daha önce oluştu)
        Schema::table('faqs', function (Blueprint $table) {
            $table->foreign('blog_id')->references('id')->on('blogs')->cascadeOnDelete();
            $table->index(['blog_id', 'active', 'sort_order']);
        });

        // Blog yazısının altında "ilgili aktiviteler"
        Schema::create('activity_blog', function (Blueprint $table) {
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('blog_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->primary(['activity_id', 'blog_id']);
        });

        // Sitenin sabit sayfaları (anasayfa, iletişim…); şimdilik sadece SEO bilgileri
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();     // front'taki route ile eşleşir: home, about…
            $table->string('name');                   // panelde görünen ad
            $table->string('path');                   // sitedeki adres: /, /about…
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
        Schema::dropIfExists('activity_blog');
        Schema::table('faqs', function (Blueprint $table) {
            $table->dropForeign(['blog_id']);
            $table->dropIndex(['blog_id', 'active', 'sort_order']);
        });
        Schema::dropIfExists('blogs');
    }
};
