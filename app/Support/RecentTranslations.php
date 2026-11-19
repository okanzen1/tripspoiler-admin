<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Arka planda (toplu çeviri sırası) biten çeviriler: Arka plan işleri sayfasında "Son çevrilenler" olarak görünür,
 * böylece sıranın ilerlediği belli olur. Son 40 kayıt, 24 saat tutulur.
 */
class RecentTranslations
{
    private const KEY = 'translations:recent';

    public static function push(Model $model, array $locales): void
    {
        $type = Str::snake(class_basename($model));
        $items = Cache::get(self::KEY, []);
        array_unshift($items, [
            'subject' => (ActivityLogger::TYPES[$type][0] ?? class_basename($model)) . ' · ' . ActivityLogger::label($model),
            'locales' => strtoupper(implode(', ', $locales)),
            'at' => now()->timestamp,
        ]);
        Cache::put(self::KEY, array_slice($items, 0, 40), now()->addDay());
    }

    /** @return list<array{subject: string, locales: string, at: int}> */
    public static function all(): array
    {
        return Cache::get(self::KEY, []);
    }

    /** Son 1 saatte biten çeviri sayısı */
    public static function lastHour(): int
    {
        $since = now()->subHour()->timestamp;

        return count(array_filter(self::all(), fn ($item) => $item['at'] >= $since));
    }
}
