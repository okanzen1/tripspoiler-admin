<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;

/**
 * Sitenin sayfa önbelleği (ön yüz: App\Http\Middleware\PageCache): sitede görünen bir içerik panelde kaydedilince /
 * silinince Setting page_cache_version değişir, sitedeki bütün hazır sayfalar eskimiş sayılır ve yeniden hazırlanır.
 * Panel isteğinde en sonda bir kez yazılır; arka plan işlerinde (ör. toplu çeviri) en fazla 15 sn'de bir.
 */
class SiteCache
{
    /** Sitede görünen içerik modelleri */
    public const MODELS = [
        \App\Models\Activity::class, \App\Models\ActivityImage::class, \App\Models\Announcement::class, \App\Models\Attraction::class,
        \App\Models\Blog::class, \App\Models\Campaign::class, \App\Models\City::class, \App\Models\CityGuide::class,
        \App\Models\CityGuideSection::class, \App\Models\CityGuideTopic::class, \App\Models\Country::class, \App\Models\Faq::class,
        \App\Models\Feature::class, \App\Models\HeroText::class, \App\Models\Language::class, \App\Models\Page::class,
        \App\Models\PageBanner::class, \App\Models\Partner::class, \App\Models\PartnerLink::class, \App\Models\Redirect::class,
        \App\Models\Review::class, \App\Models\SectionText::class, \App\Models\Step::class, \App\Models\Setting::class,
    ];

    /** Siteyi etkilemeyen, sık yazılan ayarlar (zamanlayıcı, Pinterest, Search Console…) */
    private const QUIET_SETTINGS = ['page_cache_version', 'pinterest_', 'gsc_', 'indexnow_', 'mailbox', 'scheduler'];

    private static bool $pending = false;

    private static int $lastWrite = 0;

    public static function register(): void
    {
        foreach (self::MODELS as $class) {
            $class::saved(fn (Model $model) => self::changed($model));
            $class::deleted(fn (Model $model) => self::changed($model));
        }
        app()->terminating(fn () => self::flush(force: true));
    }

    private static function changed(Model $model): void
    {
        if ($model instanceof \App\Models\Setting && self::quiet((string) $model->getKey())) {
            return;
        }
        self::$pending = true;
        // Arka plan işi (uzun süren kuyruk işçisi) bitmeyi beklemesin: 15 sn'de bir yaz
        if (app()->runningInConsole()) {
            self::flush(force: false);
        }
    }

    private static function flush(bool $force): void
    {
        if (!self::$pending || (!$force && time() - self::$lastWrite < 15 && !app()->runningUnitTests())) {
            return;
        }
        self::$pending = false;
        self::$lastWrite = time();
        rescue(fn () => Setting::put('page_cache_version', (string) now()->getTimestampMs()), null, false);
    }

    private static function quiet(string $key): bool
    {
        foreach (self::QUIET_SETTINGS as $prefix) {
            if (str_starts_with($key, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
