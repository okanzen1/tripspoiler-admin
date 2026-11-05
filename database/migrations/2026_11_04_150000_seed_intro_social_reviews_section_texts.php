<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** section_texts anahtarı → sitedeki dil dosyası ve alan eşleşmesi (eyebrow, title, title_highlight, description) */
    private const SOURCES = [
        'intro' => ['tripspoiler-intro', ['eyebrow' => 'eyebrow', 'title' => 'title', 'title_highlight' => 'title_highlight', 'description' => 'desc']],
        'social' => ['social-presence-section', ['eyebrow' => 'eyebrow', 'title' => 'title', 'title_highlight' => 'title_highlight', 'description' => 'text']],
        'reviews' => ['reviews-section', ['eyebrow' => 'eyebrow', 'title' => 'title', 'title_highlight' => 'title_highlight', 'description' => 'desc']],
    ];

    public function up(): void
    {
        $langPath = rtrim((string) env('SITE_LANG_PATH', base_path('../tripspoiler/lang')), '/');

        foreach (self::SOURCES as $key => [$file, $map]) {
            $strings = [];
            foreach (glob("{$langPath}/*/{$file}.php") ?: [] as $path) {
                $strings[basename(dirname($path))] = include $path;
            }
            $row = [];
            foreach ($map as $field => $langKey) {
                $row[$field] = json_encode((object) array_filter(array_map(fn ($s) => $s[$langKey] ?? null, $strings)), JSON_UNESCAPED_UNICODE);
            }
            DB::table('section_texts')->updateOrInsert(['key' => $key], $row + ['created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('section_texts')->whereIn('key', array_keys(self::SOURCES))->delete();
    }
};
