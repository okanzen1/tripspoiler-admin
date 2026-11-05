<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Anasayfadaki özellik kartları (ikon + başlık + kısa yazı); title ve text çok dilli JSON
        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('icon', 40)->default('sparkles');
            $table->json('title');
            $table->json('text')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Başlangıç kartları (İngilizce + Türkçe; diğer diller panelden çevrilir)
        $rows = [
            ['ticket', 'Skip-the-line tickets', 'Sıra beklemeden biletler',
                'Fast-track entry for Hagia Sophia, Topkapı Palace, Basilica Cistern and more.', 'Ayasofya, Topkapı Sarayı, Yerebatan Sarnıcı ve daha fazlası için hızlı giriş.'],
            ['percent', 'City passes, compared', 'Şehir kartları karşılaştırmalı',
                'See which pass actually pays off for the sights you plan to visit.', 'Gezmek istediğin yerler için hangi kartın gerçekten kârlı olduğunu gör.'],
            ['headphones', 'Audio guides on your phone', 'Telefonunda sesli rehber',
                'Explore at your own pace with guides you start on your own phone.', 'Kendi telefonundan başlattığın rehberlerle kendi temponda gez.'],
            ['book', 'Honest travel guides', 'Dürüst seyahat rehberleri',
                'History, opening hours and practical tips, written before you go.', 'Tarih, ziyaret saatleri ve pratik ipuçları; gitmeden önce oku.'],
            ['globe', 'In 12 languages', '12 dilde',
                'Every guide and page in your language, from English to Japanese.', 'Her rehber ve sayfa kendi dilinde; İngilizceden Japoncaya.'],
            ['zap', 'Digital tickets', 'Dijital biletler',
                'Most tickets arrive by email and work straight from your phone.', 'Biletlerin çoğu e-postayla gelir, doğrudan telefonundan kullanılır.'],
            ['shield', 'Trusted partners', 'Güvenilir partnerler',
                'Tickets come straight from established providers you can rely on.', 'Biletler doğrudan güvenilir, köklü sağlayıcılardan gelir.'],
            ['heart', 'No sponsored rankings', 'Sponsorlu sıralama yok',
                'We only recommend what we would use on our own trips.', 'Sadece kendi seyahatlerimizde kullanacağımız şeyleri öneriyoruz.'],
        ];

        foreach ($rows as $i => [$icon, $titleEn, $titleTr, $textEn, $textTr]) {
            DB::table('features')->insert([
                'icon' => $icon,
                'title' => json_encode(['en' => $titleEn, 'tr' => $titleTr], JSON_UNESCAPED_UNICODE),
                'text' => json_encode(['en' => $textEn, 'tr' => $textTr], JSON_UNESCAPED_UNICODE),
                'active' => true,
                'sort_order' => $i + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('features');
    }
};
