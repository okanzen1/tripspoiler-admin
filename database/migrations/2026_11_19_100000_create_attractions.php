<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Yer sayfaları (Topkapı Sarayı, Akropolis…): sitede /{dil}/attractions/{slug}. O yerin tüm biletleri (aktivite bağlantısı),
 * ziyaret bilgileri, resmi site, SSS. Google'da "topkapi palace tickets" gibi aramaları karşılar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attractions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->text('name');
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();
            $table->text('opening_hours')->nullable();
            $table->text('closed_days')->nullable();
            $table->text('getting_there')->nullable();
            $table->text('dress_code')->nullable();
            $table->text('good_to_know')->nullable();
            $table->text('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('official_url', 500)->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedTinyInteger('image_focus')->nullable();
            $table->boolean('active')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('activity_attraction', function (Blueprint $table) {
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attraction_id')->constrained()->cascadeOnDelete();
            $table->primary(['activity_id', 'attraction_id']);
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->foreignId('attraction_id')->nullable()->after('blog_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('attraction_id');
        });
        Schema::dropIfExists('activity_attraction');
        Schema::dropIfExists('attractions');
    }
};
