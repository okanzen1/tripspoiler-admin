<?php

namespace App\Support;

use App\Models\AffiliateClick;
use App\Models\Setting;
use App\Models\Visit;

/**
 * Analitikte sayılmayan IP'ler (Yönetim › Analitik ayarları):
 *  - analytics_ignore_ips: panele giren cihazların IP'leri (otomatik, App\Http\Middleware\MarkStaffDevice; son 20)
 *  - analytics_block_ips: elle yazılan istenmeyen IP'ler (sınırsız)
 * Site (tripspoiler/app/Support/Tracker::isIgnoredIp) iki listedeki IP'lerden gelen ziyaretleri yazmaz.
 * Eski kayıtlar purge() ile silinir: IP şifreli tutulduğu için parmak izi (ip_hash) ile eşlenir.
 */
class AnalyticsIps
{
    public const AUTO = 'analytics_ignore_ips';

    public const BLOCKED = 'analytics_block_ips';

    /** @return list<string> */
    public static function auto(): array
    {
        return self::read(self::AUTO);
    }

    /** @return list<string> */
    public static function blocked(): array
    {
        return self::read(self::BLOCKED);
    }

    /** @return list<string> iki listenin birleşimi */
    public static function all(): array
    {
        return array_values(array_unique([...self::auto(), ...self::blocked()]));
    }

    /**
     * Metinden IP listesi: satır, virgül ya da boşlukla ayrılmış; geçersizler ayrıca döner.
     *
     * @return array{0: list<string>, 1: list<string>} [geçerli, geçersiz]
     */
    public static function parse(string $text): array
    {
        $items = array_values(array_unique(array_filter(array_map('trim', preg_split('/[\s,;]+/', $text) ?: []))));
        $valid = array_values(array_filter($items, fn ($ip) => filter_var($ip, FILTER_VALIDATE_IP)));

        return [$valid, array_values(array_diff($items, $valid))];
    }

    /** @param  list<string>  $ips */
    public static function saveBlocked(array $ips): void
    {
        Setting::put(self::BLOCKED, $ips ? implode(',', $ips) : null);
    }

    /**
     * IP başına analitikteki kayıt sayısı (ziyaret + partner tıklaması).
     *
     * @param  list<string>  $ips
     * @return array<string, int>
     */
    public static function counts(array $ips): array
    {
        $hashes = collect($ips)->mapWithKeys(fn ($ip) => [Privacy::hash($ip) => $ip]);
        if ($hashes->isEmpty()) {
            return [];
        }
        $visits = Visit::whereIn('ip_hash', $hashes->keys())->selectRaw('ip_hash, COUNT(*) as total')->groupBy('ip_hash')->pluck('total', 'ip_hash');
        $clicks = AffiliateClick::whereIn('ip_hash', $hashes->keys())->selectRaw('ip_hash, COUNT(*) as total')->groupBy('ip_hash')->pluck('total', 'ip_hash');

        return $hashes->mapWithKeys(fn ($ip, $hash) => [$ip => (int) ($visits[$hash] ?? 0) + (int) ($clicks[$hash] ?? 0)])->all();
    }

    /**
     * Verilen IP'lerin analitikteki bütün kayıtlarını siler.
     *
     * @param  list<string>  $ips
     * @return int silinen kayıt sayısı
     */
    public static function purge(array $ips): int
    {
        $hashes = array_values(array_filter(array_map(fn ($ip) => Privacy::hash($ip), $ips)));
        if (!$hashes) {
            return 0;
        }

        // o ziyaretlerin sayfa içi tıklamaları da (sıcaklık haritası)
        \App\Models\PageClick::whereIn('visit_id', Visit::whereIn('ip_hash', $hashes)->select('id'))->delete();

        return Visit::whereIn('ip_hash', $hashes)->delete() + AffiliateClick::whereIn('ip_hash', $hashes)->delete();
    }

    /** @return list<string> */
    private static function read(string $key): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) Setting::get($key)))));
    }
}
