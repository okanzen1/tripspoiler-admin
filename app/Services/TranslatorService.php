<?php

namespace App\Services;
use Stichoza\GoogleTranslate\GoogleTranslate;
use App\Models\Translator;
use Illuminate\Support\Facades\Http;

class TranslatorService
{
    // public function translateSingle($text,$lang)
    // {
    //     $response = Http::withHeaders([
    //         'Authorization' => 'DeepL-Auth-Key '.config('services.deepl.key')
    //     ])->asForm()->post(
    //         'https://api-free.deepl.com/v2/translate',
    //         [
    //             'text'=>$text,
    //             'source_lang'=>'EN',
    //             'target_lang'=>strtoupper($lang)
    //         ]
    //     );

    //     return $response->json('translations.0.text') ?? $text;
    // }

    public function translateSingle($text, $lang)
    {
        if (!$text) {
            return '';
        }

        try {

            $tr = new GoogleTranslate();
            $tr->setSource('en');
            $tr->setTarget($lang);

            return $tr->translate($text);
        } catch (\Exception $e) {

            \Log::error('Translation error: ' . $e->getMessage());

            return $text;
        }
    }
}
