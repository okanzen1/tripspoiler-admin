<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

/**
 * Sitenin dilleri (eski sitedekilerle aynı). "en" kaynak dil; panelden diğerleri açılıp kapatılır.
 */
class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            ['en', 'English', 'English'],
            ['tr', 'Turkish', 'Türkçe'],
            ['de', 'German', 'Deutsch'],
            ['fr', 'French', 'Français'],
            ['es', 'Spanish', 'Español'],
            ['it', 'Italian', 'Italiano'],
            ['pt', 'Portuguese', 'Português'],
            ['ru', 'Russian', 'Русский'],
            ['ar', 'Arabic', 'العربية'],
            ['zh', 'Chinese', '中文'],
            ['ja', 'Japanese', '日本語'],
            ['ko', 'Korean', '한국어'],
        ];

        foreach ($languages as $i => [$code, $name, $native]) {
            Language::updateOrCreate(['code' => $code], ['name' => $name, 'native_name' => $native, 'sort_order' => $i]);
        }
    }
}
