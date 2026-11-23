<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Hesaplaması ağır ama sık okunan değerler (menüdeki "çevirisi eksik" ve "SEO'su eksik" sayıları): sayfa her zaman
 * hazır (önceki) değeri okur, hiçbir zaman beklemez. Bir kayıt değişince değer sadece "eski" diye işaretlenir; arka
 * planda zamanlayıcı (panel:refresh-counters, dakikada bir) yeniden hesaplar.
 *
 * Eskiden değişiklikte önbellek siliniyordu: toplu çeviri her dili kaydettikçe silinip açılan her panel sayfası bütün
 * kayıtları (400 blog × 12 dil) baştan tarıyordu, aynı anda birkaç sayfa açılınca panel kilitleniyordu.
 */
class StaleCache
{
    public static function get(string $key, Closure $build): mixed
    {
        $value = Cache::get($key);
        if ($value !== null) {
            return $value;
        }

        // İlk kez (önbellekte hiç yok): bir istek hesaplar, aynı anda gelenler onu bekler (hepsi ayrı ayrı hesaplamasın)
        try {
            return Cache::lock($key . ':build', 120)->block(60, fn () => Cache::get($key) ?? self::store($key, $build));
        } catch (Throwable $e) {
            report($e);

            return Cache::get($key) ?? [];
        }
    }

    /** Değer eskidi: arka planda yeniden hesaplansın (ucuz: tek satır yazar, sayfa beklemez) */
    public static function invalidate(string $key): void
    {
        // Testlerde hemen: kayıt ekleyen test sayıyı aynı istekte doğru görsün
        if (app()->runningUnitTests()) {
            Cache::forget($key);

            return;
        }
        rescue(fn () => Cache::add($key . ':dirty', true), null, false);
    }

    /** Eskimişse yeniden hesaplar (zamanlayıcı). true: hesaplandı */
    public static function refresh(string $key, Closure $build, bool $force = false): bool
    {
        if (!$force && Cache::has($key) && !Cache::has($key . ':dirty')) {
            return false;
        }
        // Önce işareti kaldır: hesaplama sürerken gelen değişiklik yeni işaret koyar, bir sonraki turda hesaplanır
        Cache::forget($key . ':dirty');
        self::store($key, $build);

        return true;
    }

    private static function store(string $key, Closure $build): mixed
    {
        $value = $build();
        Cache::forever($key, $value);

        return $value;
    }
}
