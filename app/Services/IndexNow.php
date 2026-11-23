<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * IndexNow (ücretsiz): Bing, Yandex, Seznam, Naver… yeni ya da değişen sayfaları anında öğrenir.
 * Sitenin kendi site haritası (sitemap.xml) okunur: her dil ayrı adres, son değişiklik tarihiyle. Haritada yeni
 * çıkan ya da tarihi değişen adresler IndexNow'a gönderilir (saatte bir: php artisan indexnow:sync).
 * Adres kurallarını tekrar yazmamak için haritayı kullanır; yayından kalkan sayfa haritadan da kalkar.
 */
class IndexNow
{
    public const ENDPOINT = 'https://api.indexnow.org/indexnow';

    /** IndexNow tek istekte en fazla 10.000 adres kabul eder */
    public const BATCH = 10000;

    public static function key(): string
    {
        return (string) config('site.indexnow_key');
    }

    public static function keyUrl(): string
    {
        return config('site.url') . '/' . self::key() . '.txt';
    }

    public static function sitemapUrl(): string
    {
        return config('site.url') . '/sitemap.xml';
    }

    /**
     * Haritayı okur, yeni / değişen adresleri gönderir. Sonuç Setting'e yazılır (panelde görünür).
     *
     * @return array{found: int, sent: int, status: ?int, error: ?string}
     */
    public function sync(): array
    {
        $result = ['found' => 0, 'sent' => 0, 'status' => null, 'error' => null];
        try {
            $entries = $this->sitemap();
            $result['found'] = count($entries);

            $known = DB::table('indexnow_urls')->pluck('lastmod', 'url_hash')->all();
            $changed = array_filter($entries, fn ($lastmod, $url) => !array_key_exists(sha1($url), $known) || $known[sha1($url)] !== $lastmod, ARRAY_FILTER_USE_BOTH);

            foreach (array_chunk($changed, self::BATCH, true) as $batch) {
                $result['status'] = $this->submit(array_keys($batch));
                $now = now();
                foreach (array_chunk($batch, 500, true) as $rows) {
                    DB::table('indexnow_urls')->upsert(array_map(fn ($url, $lastmod) => [
                        'url_hash' => sha1($url), 'url' => $url, 'lastmod' => $lastmod, 'submitted_at' => $now,
                    ], array_keys($rows), $rows), ['url_hash'], ['url', 'lastmod', 'submitted_at']);
                }
                $result['sent'] += count($batch);
            }
        } catch (\Throwable $e) {
            $result['error'] = Str::limit($e->getMessage(), 300);
        }

        Setting::put('indexnow_last_run', (string) now()->timestamp);
        if ($result['sent'] || $result['error']) {
            Setting::put('indexnow_last', json_encode([...$result, 'at' => now()->timestamp]));
        }

        return $result;
    }

    /** Bildirim geçmişini siler: bir sonraki çalışmada haritadaki bütün adresler yeniden gönderilir */
    public function reset(): void
    {
        DB::table('indexnow_urls')->delete();
    }

    /** @return array{found: int, sent: int, status: ?int, error: ?string, at: int}|null */
    public static function last(): ?array
    {
        return json_decode((string) Setting::get('indexnow_last'), true) ?: null;
    }

    /** Anahtar dosyası sitede doğru mu (arama motoru bununla doğrular) */
    public static function keyFileOk(): bool
    {
        try {
            $response = Http::timeout(10)->get(self::keyUrl());

            return $response->ok() && trim($response->body()) === self::key();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Site haritasındaki adresler → son değişiklik tarihi (yoksa boş).
     *
     * @return array<string, string>
     */
    private function sitemap(): array
    {
        $xml = $this->loadXml(self::sitemapUrl());
        $host = parse_url(config('site.url'), PHP_URL_HOST);

        // Site haritası dizini (sitemapindex): alt haritalar tek tek okunur
        if ($xml->getName() === 'sitemapindex') {
            $entries = [];
            foreach ($xml->sitemap as $child) {
                $loc = trim((string) $child->loc);
                if ($loc !== '' && parse_url($loc, PHP_URL_HOST) === $host) {
                    $entries += $this->urls($this->loadXml($loc), $host);
                }
            }

            return $entries;
        }

        return $this->urls($xml, $host);
    }

    private function loadXml(string $url): \SimpleXMLElement
    {
        $response = Http::timeout(60)->accept('application/xml')->get($url);
        if ($response->failed()) {
            throw new \RuntimeException(__('Site haritası okunamadı (:code).', ['code' => $response->status()]));
        }
        $xml = @simplexml_load_string($response->body());
        if ($xml === false) {
            throw new \RuntimeException(__('Site haritası okunamadı (geçersiz XML).'));
        }

        return $xml;
    }

    /** @return array<string, string> adres → son değişiklik */
    private function urls(\SimpleXMLElement $xml, ?string $host): array
    {
        $entries = [];
        foreach ($xml->url as $node) {
            $url = trim((string) $node->loc);
            if ($url === '' || parse_url($url, PHP_URL_HOST) !== $host) {
                continue;
            }
            $lastmod = trim((string) $node->lastmod);
            $entries[$url] = $lastmod !== '' ? Carbon::parse($lastmod)->toIso8601String() : '';
        }

        return $entries;
    }

    /** @param list<string> $urls */
    private function submit(array $urls): int
    {
        $response = Http::timeout(60)->acceptJson()->asJson()->post(self::ENDPOINT, [
            'host' => parse_url(config('site.url'), PHP_URL_HOST),
            'key' => self::key(),
            'keyLocation' => self::keyUrl(),
            'urlList' => array_values($urls),
        ]);
        // 200: alındı, 202: alındı (anahtar doğrulaması sürüyor)
        if (!in_array($response->status(), [200, 202], true)) {
            throw new \RuntimeException(__('IndexNow hatası (:code): :message', [
                'code' => $response->status(),
                'message' => $response->json('message') ?? (Str::limit(trim($response->body()), 200) ?: match ($response->status()) {
                    403 => __('Anahtar dosyası bulunamadı ya da yanlış.'),
                    422 => __('Adresler sitenin adresiyle uyuşmuyor.'),
                    429 => __('Çok sık gönderildi, sonra tekrar denenecek.'),
                    default => '',
                }),
            ]));
        }

        return $response->status();
    }
}
