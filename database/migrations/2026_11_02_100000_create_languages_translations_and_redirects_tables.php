<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sitenin dilleri. "en" kaynak dildir (panelde yazılan); diğerleri ondan çevrilir. Front aktif dilleri buradan okur.
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();       // URL'deki önek: en, de, zh…
            $table->string('name', 60);                 // İngilizce adı
            $table->string('native_name', 60);          // kendi dilinde adı (dil seçicide)
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Bir kaydın bir dile hangi İngilizce metinden çevrildiği. İngilizce değişince özet (hash) tutmaz → çeviri eskimiş sayılır.
        Schema::create('translation_states', function (Blueprint $table) {
            $table->id();
            $table->morphs('translatable');
            $table->string('locale', 10);
            $table->char('source_hash', 64);
            $table->timestamp('translated_at');
            $table->unique(['translatable_type', 'translatable_id', 'locale'], 'translation_states_unique');
        });

        // Eski adres → yeni adres (301). Slug değişince otomatik eklenir; front her istekte buraya bakar, 404 vermez.
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path', 700)->unique();   // ör. /de/activities/eski-adres (çözülmüş, % kodsuz)
            $table->string('to_path', 700);
            $table->unsignedInteger('hits')->default(0);  // front artırır
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirects');
        Schema::dropIfExists('translation_states');
        Schema::dropIfExists('languages');
    }
};
