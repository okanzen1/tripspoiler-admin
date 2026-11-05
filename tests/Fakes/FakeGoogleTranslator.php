<?php

namespace Tests\Fakes;

use App\Services\Translation\GoogleTranslator;
use RuntimeException;

/**
 * Google'a gitmeden çevirir: her satırın başına "[dil] " ekler. Gönderilen istekler kaydedilir.
 */
class FakeGoogleTranslator extends GoogleTranslator
{
    /** @var list<array{text: string, locale: string}> */
    public array $requests = [];

    public bool $fail = false;

    protected function request(string $text, string $locale): string
    {
        if ($this->fail) {
            throw new RuntimeException('Google Çeviri şu an yanıt vermiyor.');
        }
        $this->requests[] = ['text' => $text, 'locale' => $locale];

        return implode("\n", array_map(fn ($line) => "[{$locale}] {$line}", explode("\n", $text)));
    }
}
