<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Anasayfa bölümlerinin aç/kapa durumu (Setting "sections_disabled" = "reviews,social"). Kapalı bölüm sitenin
 * hiçbir sayfasında görünmez. Site de aynı anahtarı okur (tripspoiler/app/Support/SiteSections.php).
 */
class SiteSections
{
    public const KEYS = ['steps', 'blog', 'features', 'announcements', 'intro', 'social', 'reviews', 'city_entry', 'partners', 'home_popular', 'home_faq', 'rates', 'weather', 'time'];

    /** Sadece anasayfayı etkileyenler (diğer sayfalardaki SSS / popüler aktiviteler yerinde kalır) */
    public const HOME_ONLY = ['home_popular', 'home_faq'];

    /** @return list<string> */
    public static function disabled(): array
    {
        return array_values(array_intersect(self::KEYS, explode(',', (string) Setting::get('sections_disabled'))));
    }

    public static function enabled(string $key): bool
    {
        return !in_array($key, self::disabled(), true);
    }

    public static function toggle(string $key): bool
    {
        $disabled = self::disabled();
        $enabled = in_array($key, $disabled, true);
        $disabled = $enabled ? array_diff($disabled, [$key]) : [...$disabled, $key];
        Setting::put('sections_disabled', $disabled ? implode(',', array_unique($disabled)) : null);

        return $enabled;
    }
}
