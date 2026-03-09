<?php

namespace App\Services;

use App\Models\Translator;
use Illuminate\Support\Facades\Http;

class TranslatorService
{
    public function translateSingle($text,$lang)
    {
        $response = Http::withHeaders([
            'Authorization' => 'DeepL-Auth-Key '.config('services.deepl.key')
        ])->asForm()->post(
            'https://api-free.deepl.com/v2/translate',
            [
                'text'=>$text,
                'source_lang'=>'EN',
                'target_lang'=>strtoupper($lang)
            ]
        );

        return $response->json('translations.0.text') ?? $text;
    }
}