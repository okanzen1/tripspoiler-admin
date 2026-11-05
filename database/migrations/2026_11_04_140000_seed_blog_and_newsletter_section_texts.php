<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Blog bölümü ve bülten kutusunun başlangıç yazıları: sitenin çevirilerinden (12 dil).
     * newsletter: title = başlık, description = açıklama, eyebrow = buton yazısı.
     */
    public function up(): void
    {
        $langPath = rtrim((string) env('SITE_LANG_PATH', base_path('../tripspoiler/lang')), '/');
        $read = function (string $file) use ($langPath) {
            $out = [];
            foreach (glob("{$langPath}/*/{$file}.php") ?: [] as $path) {
                $out[basename(dirname($path))] = include $path;
            }
            return $out;
        };
        $pick = fn (array $strings, string $key) => json_encode((object) array_filter(array_map(fn ($s) => $s[$key] ?? null, $strings)), JSON_UNESCAPED_UNICODE);

        $blog = $read('most-popular-blog');
        $newsletter = $read('newsletter-subscribe');

        foreach ([
            'blog' => ['eyebrow' => $pick($blog, 'eyebrow'), 'title' => $pick($blog, 'title'), 'title_highlight' => $pick($blog, 'title_highlight'), 'description' => $pick($blog, 'desc')],
            'newsletter' => ['eyebrow' => $pick($newsletter, 'button'), 'title' => $pick($newsletter, 'title'), 'title_highlight' => null, 'description' => $pick($newsletter, 'desc')],
        ] as $key => $values) {
            DB::table('section_texts')->updateOrInsert(['key' => $key], $values + ['created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('section_texts')->whereIn('key', ['blog', 'newsletter'])->delete();
    }
};
