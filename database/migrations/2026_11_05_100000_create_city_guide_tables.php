<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Şehir rehberi: alanlar (ör. "Yeme içme"; panelden yenisi eklenebilir), her şehrin her alana yazdığı içerik
 * ve şehir sayfasının üstündeki genel başlık/açıklama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('city_guide_topics', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();      // sitedeki çapa: #food
            $table->json('name');                      // sitedeki kısa ad (çok dilli): "Yeme içme"
            $table->string('icon', 40)->default('sparkles');
            $table->string('example')->nullable();     // panelde örnek başlık
            $table->unsignedSmallInteger('sort_order')->default(0); // yeni şehirlerde varsayılan sıra
            $table->boolean('active')->default(true);  // kapalı alan hiçbir şehirde görünmez
            $table->timestamps();
        });

        Schema::create('city_guide_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained('city_guide_topics')->cascadeOnDelete();
            $table->json('title')->nullable();
            $table->json('content')->nullable();       // editörden gelen HTML
            $table->string('image_path')->nullable();  // isteğe bağlı kapak
            $table->boolean('active')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['city_id', 'topic_id']);
            $table->index(['city_id', 'active', 'sort_order']);
        });

        Schema::create('city_guides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('title')->nullable();
            $table->json('description')->nullable();
            $table->timestamps();
        });

        // Hazır 10 alan (adları 12 dilde)
        $topics = [
            ['sights', 'landmark', 'İstanbul\'da mutlaka görmen gereken yerler', ['en' => 'Things to see', 'tr' => 'Gezilecek yerler', 'de' => 'Sehenswürdigkeiten', 'es' => 'Qué ver', 'fr' => 'À voir', 'it' => 'Cosa vedere', 'pt' => 'O que ver', 'ru' => 'Что посмотреть', 'ar' => 'أماكن تستحق الزيارة', 'ja' => '見どころ', 'ko' => '볼거리', 'zh' => '景点']],
            ['food', 'utensils', 'İstanbul\'da en iyi nerede yemek yenir?', ['en' => 'Food & drink', 'tr' => 'Yeme içme', 'de' => 'Essen & Trinken', 'es' => 'Comer y beber', 'fr' => 'Manger et boire', 'it' => 'Mangiare e bere', 'pt' => 'Comer e beber', 'ru' => 'Еда и напитки', 'ar' => 'الطعام والشراب', 'ja' => 'グルメ', 'ko' => '음식과 음료', 'zh' => '美食']],
            ['stay', 'bed', 'İstanbul\'da hangi semtte kalmalı?', ['en' => 'Where to stay', 'tr' => 'Nerede kalınır', 'de' => 'Unterkunft', 'es' => 'Dónde alojarse', 'fr' => 'Où loger', 'it' => 'Dove dormire', 'pt' => 'Onde ficar', 'ru' => 'Где остановиться', 'ar' => 'أين تقيم', 'ja' => '宿泊エリア', 'ko' => '숙소 지역', 'zh' => '住宿区域']],
            ['getting-around', 'bus', 'Metro, tramvay, vapur: İstanbulkart rehberi', ['en' => 'Getting around', 'tr' => 'Şehir içi ulaşım', 'de' => 'Unterwegs in der Stadt', 'es' => 'Cómo moverse', 'fr' => 'Se déplacer', 'it' => 'Come muoversi', 'pt' => 'Como se locomover', 'ru' => 'Транспорт по городу', 'ar' => 'التنقل في المدينة', 'ja' => '市内の移動', 'ko' => '시내 교통', 'zh' => '市内交通']],
            ['airport', 'plane', 'İstanbul Havalimanı\'ndan merkeze nasıl gidilir?', ['en' => 'From the airport', 'tr' => 'Havalimanından şehre', 'de' => 'Vom Flughafen', 'es' => 'Desde el aeropuerto', 'fr' => 'Depuis l’aéroport', 'it' => 'Dall’aeroporto', 'pt' => 'Do aeroporto', 'ru' => 'Из аэропорта', 'ar' => 'من المطار', 'ja' => '空港からのアクセス', 'ko' => '공항에서 시내로', 'zh' => '机场交通']],
            ['when-to-go', 'sun', 'İstanbul\'a gitmek için en iyi mevsim', ['en' => 'When to go', 'tr' => 'Ne zaman gidilir', 'de' => 'Beste Reisezeit', 'es' => 'Cuándo ir', 'fr' => 'Quand partir', 'it' => 'Quando andare', 'pt' => 'Quando ir', 'ru' => 'Когда ехать', 'ar' => 'أفضل وقت للزيارة', 'ja' => 'ベストシーズン', 'ko' => '여행 적기', 'zh' => '最佳旅行时间']],
            ['itineraries', 'route', 'İstanbul\'da 1, 2 ve 3 günlük gezi planı', ['en' => 'Itineraries', 'tr' => 'Hazır rotalar', 'de' => 'Reiserouten', 'es' => 'Itinerarios', 'fr' => 'Itinéraires', 'it' => 'Itinerari', 'pt' => 'Roteiros', 'ru' => 'Маршруты', 'ar' => 'مسارات جاهزة', 'ja' => 'モデルコース', 'ko' => '추천 일정', 'zh' => '行程推荐']],
            ['day-trips', 'compass', 'İstanbul\'dan günübirlik kaçamaklar', ['en' => 'Day trips', 'tr' => 'Günübirlik geziler', 'de' => 'Tagesausflüge', 'es' => 'Excursiones de un día', 'fr' => 'Excursions d’une journée', 'it' => 'Gite di un giorno', 'pt' => 'Bate-voltas', 'ru' => 'Однодневные поездки', 'ar' => 'رحلات اليوم الواحد', 'ja' => '日帰り旅行', 'ko' => '당일치기 여행', 'zh' => '一日游']],
            ['shopping', 'bag', 'Kapalıçarşı, Mısır Çarşısı ve fazlası', ['en' => 'Shopping & markets', 'tr' => 'Alışveriş ve çarşılar', 'de' => 'Shopping & Märkte', 'es' => 'Compras y mercados', 'fr' => 'Shopping et marchés', 'it' => 'Shopping e mercati', 'pt' => 'Compras e mercados', 'ru' => 'Шопинг и рынки', 'ar' => 'التسوق والأسواق', 'ja' => 'ショッピングと市場', 'ko' => '쇼핑과 시장', 'zh' => '购物与市集']],
            ['practical-tips', 'info', 'Bahşiş, cami kuralları, güvenlik, internet', ['en' => 'Practical tips', 'tr' => 'Pratik bilgiler', 'de' => 'Praktische Tipps', 'es' => 'Consejos prácticos', 'fr' => 'Infos pratiques', 'it' => 'Consigli pratici', 'pt' => 'Dicas práticas', 'ru' => 'Полезные советы', 'ar' => 'نصائح عملية', 'ja' => '旅の基本情報', 'ko' => '여행 팁', 'zh' => '实用贴士']],
        ];

        foreach ($topics as $i => [$key, $icon, $example, $names]) {
            DB::table('city_guide_topics')->insert([
                'key' => $key,
                'name' => json_encode($names, JSON_UNESCAPED_UNICODE),
                'icon' => $icon,
                'example' => 'Örn: ' . $example,
                'sort_order' => $i + 1,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('city_guides');
        Schema::dropIfExists('city_guide_sections');
        Schema::dropIfExists('city_guide_topics');
    }
};
