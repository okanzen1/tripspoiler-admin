<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Anasayfa bölümlerinin başlık yazıları (key: "steps" …); alanlar çok dilli JSON, panel "en" düzenler
        Schema::create('section_texts', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique();
            $table->json('eyebrow')->nullable();
            $table->json('title')->nullable();
            $table->json('title_highlight')->nullable();
            $table->json('description')->nullable();
            $table->timestamps();
        });

        // "Nasıl çalışır" adımları; title, text, cta çok dilli; link site içi adres (/blog) ya da tam adres
        Schema::create('steps', function (Blueprint $table) {
            $table->id();
            $table->string('icon', 40)->default('sparkles');
            $table->json('title');
            $table->json('text')->nullable();
            $table->json('cta')->nullable();
            $table->string('link', 500)->nullable();
            $table->boolean('active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Başlangıç değerleri: sitenin şu anki çevirileri (lang/{dil}/steps-section.php)
        $langPath = rtrim((string) env('SITE_LANG_PATH', base_path('../tripspoiler/lang')), '/');
        $strings = [];
        foreach (glob($langPath . '/*/steps-section.php') ?: [] as $file) {
            $strings[basename(dirname($file))] = include $file;
        }
        $pick = fn (string $key) => json_encode((object) array_filter(array_map(fn ($s) => $s[$key] ?? null, $strings)), JSON_UNESCAPED_UNICODE);

        DB::table('section_texts')->insert([
            'key' => 'steps',
            'eyebrow' => $pick('eyebrow'),
            'title' => $pick('title'),
            'title_highlight' => $pick('title_highlight'),
            'description' => $pick('desc'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([1 => ['book', '/blog'], 2 => ['percent', '/activities'], 3 => ['ticket', '/activities']] as $n => [$icon, $link]) {
            DB::table('steps')->insert([
                'icon' => $icon,
                'title' => $pick("step{$n}_title"),
                'text' => $pick("step{$n}_text"),
                'cta' => $pick("step{$n}_cta"),
                'link' => $link,
                'active' => true,
                'sort_order' => $n,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('steps');
        Schema::dropIfExists('section_texts');
    }
};
