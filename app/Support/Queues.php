<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Arka plan işlerinin sıraları (öncelik sırasıyla). Kuyruk işçisi (routes/console.php) önce HIGH'ı bitirir, sonra
 * DEFAULT, sonra BULK_NEXT, en son BULK. Böylece panelden tek kayda basılan "Çevir" binlerce toplu çevirinin arkasında
 * beklemez; toplu çeviride de bir kaydın bütün dilleri bitmeden sıradaki kayda geçilmez (devamı BULK_NEXT'e girer).
 */
class Queues
{
    /** Panelden tek kayıt / tek bölüm çevirisi */
    public const HIGH = 'high';

    /** Diğer işler (e-posta, SSS kaydedilince otomatik çeviri, toplu çeviriyi sıraya alma…) */
    public const DEFAULT = 'default';

    /** Toplu çeviride yarım kalan kaydın kalan dilleri: sıradaki kayda geçmeden önce biter */
    public const BULK_NEXT = 'bulk-next';

    /** Toplu çeviri: kayıt kayıt */
    public const BULK = 'bulk';

    public static function worker(): string
    {
        return implode(',', [self::HIGH, self::DEFAULT, self::BULK_NEXT, self::BULK]);
    }

    /** Toplu çeviri sırasında bekleyen kayıt sayısı (queue sütunu indeksli: hızlı) */
    public static function bulkCount(): int
    {
        return (int) rescue(fn () => DB::table('jobs')->whereIn('queue', [self::BULK, self::BULK_NEXT])->count(), 0, false);
    }

    /** Zamanlayıcı (cron) son 3 dakikada çalıştı mı: çalışmıyorsa sıradaki işler işlenmez */
    public static function schedulerAlive(): bool
    {
        return \Illuminate\Support\Facades\Cache::get('scheduler:last_run', 0) > time() - 180;
    }
}
