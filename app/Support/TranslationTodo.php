<?php

namespace App\Support;

use App\Models\TranslationState;
use App\Services\Translation\ModelTranslator;
use Illuminate\Support\Collection;
use Throwable;

/**
 * "Çevirisi unutulanlar": İngilizcesi girilmiş ama bir ya da daha fazla dilde çevirisi eksik / eskimiş kayıtlar.
 * Panelde menüde, Yönetim kartlarında, sayfa satırlarında ve listelerde mor sayı olarak gösterilir; böylece
 * hangi içeriğin çevrilmediği iz sürerek bulunur. Kayıtlar ya da çeviriler değişince önbellek silinir (AppServiceProvider).
 */
class TranslationTodo
{
    private const CACHE = 'translation-todo';

    /** Menüdeki yerler → kayıt türleri */
    public const AREAS = [
        'activities' => ['activity'],
        'attractions' => ['attraction'],
        'blogs' => ['blog'],
        'city-guides' => ['city-guide', 'city-guide-section', 'city-guide-topic'],
        'faqs' => ['faq'],
        'campaigns' => ['campaign'],
        'announcements' => ['announcement'],
        'management' => ['page', 'hero', 'section', 'step', 'feature'],
    ];

    private static ?Collection $items = null;

    /**
     * @return Collection<int, array{type: string, id: int, url: string, title: string, locales: int}>
     */
    public static function items(): Collection
    {
        // Hazır değer okunur, sayfa hesaplama beklemez (App\Support\StaleCache; zamanlayıcı dakikada bir yeniler)
        return self::$items ??= collect(StaleCache::get(self::CACHE, fn () => self::safeBuild()));
    }

    /** Kayıt değişti: liste arka planda yeniden hesaplanacak (panel:refresh-counters) */
    public static function forget(): void
    {
        self::$items = null;
        StaleCache::invalidate(self::CACHE);
    }

    /** Zamanlayıcıdan: eskimişse yeniden hesapla */
    public static function refresh(bool $force = false): bool
    {
        self::$items = null;

        return StaleCache::refresh(self::CACHE, fn () => self::safeBuild(), $force);
    }

    private static function safeBuild(): array
    {
        try {
            return self::build();
        } catch (Throwable $e) {
            report($e);

            return [];
        }
    }

    public static function count(string ...$types): int
    {
        // aktivite / blog: kendi SSS'leri de sayılır
        return self::items()->filter(fn ($item) => in_array($item['type'], $types, true)
            || ($item['parent'] ?? null) && in_array(strtok($item['parent'], ':'), array_diff($types, ['faq']), true))->count();
    }

    public static function area(string $area): int
    {
        return self::count(...self::AREAS[$area] ?? []);
    }

    public static function total(): int
    {
        return self::items()->count();
    }

    /** Düzenleme adresi verilen adreslerden biriyle başlayan kayıt sayısı (ör. bir sayfanın tüm alt sayfaları) */
    public static function under(string ...$urls): int
    {
        $prefixes = array_filter(array_map(fn ($url) => rtrim(strtok($url, '#?'), '/'), $urls));

        return self::items()->filter(function ($item) use ($prefixes) {
            $url = rtrim(strtok($item['url'], '#?'), '/');
            foreach ($prefixes as $prefix) {
                if ($url === $prefix || str_starts_with($url, $prefix . '/')) {
                    return true;
                }
            }

            return false;
        })->count();
    }

    public static function has(string $type, int|string|null $id): bool
    {
        return $id !== null && self::items()->contains(fn ($item) => $item['type'] === $type && $item['id'] == $id || ($item['parent'] ?? null) === $type . ':' . $id);
    }

    private static function build(): array
    {
        $translator = app(ModelTranslator::class);
        $items = [];

        foreach (ModelTranslator::TYPES as $type => $class) {
            $states = TranslationState::where('translatable_type', (new $class)->getMorphClass())->get()->groupBy('translatable_id');
            foreach ($class::all() as $model) {
                $status = $translator->status($model, $states->get($model->getKey(), collect())->pluck('source_hash', 'locale'));
                $missing = array_keys(array_filter($status, fn ($state) => $state !== 'ok'));
                $pending = count($missing);
                if (!$pending) {
                    continue;
                }
                try {
                    $owner = ModelTranslator::owner($type, $model);
                } catch (Throwable) {
                    continue; // düzenleme sayfası bulunamayan eski kayıt
                }
                // SSS bir aktiviteye / blog yazısına aitse o kaydın listesinde de görünsün
                $parent = $type === 'faq' ? ($model->activity_id ? 'activity:' . $model->activity_id : ($model->blog_id ? 'blog:' . $model->blog_id : null)) : null;
                $items[] = ['type' => $type, 'id' => $model->getKey(), 'url' => $owner['url'], 'title' => (string) $owner['title'], 'locales' => $pending, 'missing' => $missing, 'parent' => $parent];
            }
        }

        return $items;
    }
}
