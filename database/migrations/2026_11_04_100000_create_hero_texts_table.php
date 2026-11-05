<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Alan → sitedeki eski çeviri anahtarı (lang/{dil}/home-hero.php) */
    private const FIELDS = [
        'eyebrow' => 'eyebrow',
        'title_1' => 'title_1',
        'title_2' => 'title_2',
        'title_3' => 'title_3',
        'title_highlight' => 'title_highlight',
        'description' => 'desc',
    ];

    public function up(): void
    {
        // Anasayfa hero'sundaki yazılar (tek satır); her alan çok dilli JSON, panel "en" düzenler
        Schema::create('hero_texts', function (Blueprint $table) {
            $table->id();
            foreach (array_keys(self::FIELDS) as $field) {
                $table->json($field)->nullable();
            }
            $table->timestamps();
        });

        // Başlangıç değerleri: sitenin şu anki çevirileri (SITE_LANG_PATH verilmişse oradan)
        $langPath = rtrim((string) env('SITE_LANG_PATH', base_path('../tripspoiler/lang')), '/');
        $values = array_fill_keys(array_keys(self::FIELDS), []);
        foreach (glob($langPath . '/*/home-hero.php') ?: [] as $file) {
            $locale = basename(dirname($file));
            $strings = include $file;
            foreach (self::FIELDS as $field => $key) {
                if (filled($strings[$key] ?? null)) {
                    $values[$field][$locale] = $strings[$key];
                }
            }
        }

        DB::table('hero_texts')->insert(array_map(fn ($v) => json_encode((object) $v, JSON_UNESCAPED_UNICODE), $values)
            + ['created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_texts');
    }
};
