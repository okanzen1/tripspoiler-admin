<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Arka planda süren tek kayıt çevirileri (Google Çeviri, cevap gönderildikten sonra çalışır).
 * Panel her sayfada sağ üstte durumunu gösterir: kaç dil bitti, bitti mi, hata mı.
 *
 * İş: key ("hero:1"), title, url, total, done, current, status (running|done|failed), message,
 *     started_at, updated_at, finished_at (unix saniye)
 */
class TranslationJobs
{
    private const INDEX = 'translation-jobs:index';

    private const TTL = 3600;

    /** Bu kadar saniye ses çıkmazsa (sunucu süreci öldüyse) iş yarıda kalmış sayılır */
    private const STALE = 180;

    /** Biten iş, sayfa değişse de bu kadar saniye bildirimde görünür */
    private const RECENT = 120;

    public static function get(string $key): ?array
    {
        $job = Cache::get(self::cacheKey($key));
        if ($job && $job['status'] === 'running' && $job['updated_at'] < time() - self::STALE) {
            $job = ['status' => 'failed', 'message' => __('Çeviri yarıda kaldı, tekrar dene.'), 'finished_at' => $job['updated_at']] + $job;
        }

        return $job;
    }

    public static function running(string $key): bool
    {
        return (self::get($key)['status'] ?? null) === 'running';
    }

    /**
     * @param  list<string>  $locales
     */
    public static function start(string $key, string $title, string $url, array $locales): array
    {
        $now = time();
        $job = [
            'key' => $key, 'title' => $title, 'url' => $url, 'total' => count($locales), 'done' => 0, 'current' => null,
            'status' => 'running', 'message' => null, 'started_at' => $now, 'updated_at' => $now, 'finished_at' => null,
        ];
        self::put($job);

        Cache::lock(self::INDEX . ':lock', 5)->block(5, function () use ($key) {
            $index = array_values(array_unique([...Cache::get(self::INDEX, []), $key]));
            Cache::put(self::INDEX, array_slice($index, -30), self::TTL);
        });

        return $job;
    }

    public static function progress(string $key, string $locale): void
    {
        if ($job = Cache::get(self::cacheKey($key))) {
            self::put(['done' => $job['done'] + 1, 'current' => $locale, 'updated_at' => time()] + $job);
        }
    }

    public static function finish(string $key, string $message): void
    {
        self::close($key, 'done', $message);
    }

    public static function fail(string $key, string $message): void
    {
        self::close($key, 'failed', $message);
    }

    /** Sürenler ve az önce bitenler (panel açılınca bildirimde gösterilir) */
    public static function recent(): array
    {
        return collect(Cache::get(self::INDEX, []))
            ->map(fn ($key) => self::get($key))
            ->filter(fn ($job) => $job && ($job['status'] === 'running' || $job['finished_at'] >= time() - self::RECENT))
            ->values()->all();
    }

    private static function close(string $key, string $status, string $message): void
    {
        if ($job = Cache::get(self::cacheKey($key))) {
            $now = time();
            self::put(['status' => $status, 'message' => $message, 'updated_at' => $now, 'finished_at' => $now] + $job);
        }
    }

    private static function put(array $job): void
    {
        Cache::put(self::cacheKey($job['key']), $job, self::TTL);
    }

    private static function cacheKey(string $key): string
    {
        return 'translation-job:' . $key;
    }
}
