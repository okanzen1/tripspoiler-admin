<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** "Şehirler çağrısı" bölümünün (büyük resimli kart) yazıları: sitenin çevirilerinden */
    public function up(): void
    {
        $langPath = rtrim((string) env('SITE_LANG_PATH', base_path('../tripspoiler/lang')), '/');
        $strings = [];
        foreach (glob("{$langPath}/*/city-entry-section.php") ?: [] as $path) {
            $strings[basename(dirname($path))] = include $path;
        }
        $pick = fn (string $key) => json_encode((object) array_filter(array_map(fn ($s) => $s[$key] ?? null, $strings)), JSON_UNESCAPED_UNICODE);

        DB::table('section_texts')->updateOrInsert(['key' => 'city_entry'], [
            'eyebrow' => $pick('eyebrow'), 'title' => $pick('title'), 'title_highlight' => $pick('title_highlight'),
            'description' => $pick('text'), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('section_texts')->where('key', 'city_entry')->delete();
    }
};
