<?php

namespace App\Support;

use App\Models\Activity;
use App\Models\Attraction;
use App\Models\Blog;
use App\Models\CityGuide;
use App\Models\CityGuideSection;
use App\Models\CityGuideTopic;
use App\Models\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Throwable;

/**
 * "SEO'su / içeriği eksik olanlar": İngilizce SEO başlığı, SEO açıklaması ya da asıl metni boş kalan kayıtlar
 * (aktivite, blog, yer, sayfa SEO'su, şehir rehberi girişi ve alanları). Panelde menüde turuncu sayı, listelerin
 * üstünde turuncu kutu olarak gösterilir; böylece Google'a eksik giden sayfa gözden kaçmaz.
 * Kayıt değişince önbellek silinir (AppServiceProvider).
 */
class ContentTodo
{
    private const CACHE = 'content-todo';

    /** Menüdeki yerler → kayıt türleri */
    public const AREAS = [
        'activities' => ['activity'],
        'attractions' => ['attraction'],
        'blogs' => ['blog'],
        'city-guides' => ['city-guide', 'city-guide-section'],
        'management' => ['page'],
    ];

    /** Önbelleği silen modeller */
    public const MODELS = [Activity::class, Attraction::class, Blog::class, Page::class, CityGuide::class, CityGuideSection::class, CityGuideTopic::class];

    private static ?Collection $items = null;

    /**
     * @return Collection<int, array{type: string, id: int, url: string, title: string, missing: list<string>}>
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
        return self::items()->whereIn('type', $types)->count();
    }

    public static function area(string $area): int
    {
        return self::count(...self::AREAS[$area] ?? []);
    }

    public static function total(): int
    {
        return self::items()->count();
    }

    /** Bu kaydın eksikleri (düzenleme formunun üstündeki uyarı için); eksik yoksa boş */
    public static function missing(string $type, int|string|null $id): array
    {
        return $id === null ? [] : (self::items()->first(fn ($i) => $i['type'] === $type && $i['id'] == $id)['missing'] ?? []);
    }

    private static function build(): array
    {
        $items = [];
        $add = function (string $type, Model $model, string $url, string $title, array $checks) use (&$items) {
            $missing = array_keys(array_filter($checks, fn ($ok) => !$ok));
            if ($missing) {
                $items[] = ['type' => $type, 'id' => $model->getKey(), 'url' => $url, 'title' => $title, 'missing' => $missing];
            }
        };

        foreach (Activity::orderBy('city_id')->orderBy('sort_order')->get() as $m) {
            $add('activity', $m, route('activities.edit', $m), self::en($m, 'name') ?: '#' . $m->id, [
                __('SEO başlığı') => self::filled($m, 'meta_title'),
                __('SEO açıklaması') => self::filled($m, 'meta_description'),
                __('Açıklama') => self::filled($m, 'description'),
            ]);
        }

        foreach (Blog::latest('id')->get() as $m) {
            $add('blog', $m, route('blogs.edit', $m), self::en($m, 'title') ?: '#' . $m->id, [
                __('SEO başlığı') => self::filled($m, 'meta_title'),
                __('SEO açıklaması') => self::filled($m, 'meta_description'),
                __('Özet') => self::filled($m, 'excerpt'),
                __('Yazı') => self::filled($m, 'content'),
            ]);
        }

        foreach (Attraction::orderBy('city_id')->orderBy('sort_order')->get() as $m) {
            $add('attraction', $m, route('attractions.edit', $m), self::en($m, 'name') ?: '#' . $m->id, [
                __('SEO başlığı') => self::filled($m, 'meta_title'),
                __('SEO açıklaması') => self::filled($m, 'meta_description'),
                __('Kısa özet') => self::filled($m, 'summary'),
                __('Yazı') => self::filled($m, 'content'),
            ]);
        }

        foreach (Page::orderBy('sort_order')->get() as $m) {
            if ($m->hasOwnSeo()) {
                $add('page', $m, route('pages.seo', $m), $m->name . ' · SEO', [
                    __('SEO başlığı') => self::filled($m, 'meta_title'),
                    __('SEO açıklaması') => self::filled($m, 'meta_description'),
                ]);
            }
        }

        // şehir rehberi: giriş yazısı + açık alanların hepsi yazılmış mı
        $topics = CityGuideTopic::where('active', true)->get();
        $sections = CityGuideSection::all()->groupBy('city_id');
        foreach (CityGuide::with('city')->get() as $m) {
            $city = $m->city?->name ?? '#' . $m->city_id;
            $written = $sections->get($m->city_id, collect())->filter->isWritten()->pluck('topic_id')->all();
            $empty = $topics->reject(fn ($t) => in_array($t->id, $written, true))->map(fn ($t) => $t->getTranslation('name', 'en', false))->filter()->values();
            $checks = [
                __('Başlık') => self::filled($m, 'title'),
                __('Giriş yazısı') => self::filled($m, 'description'),
            ];
            if ($empty->isNotEmpty()) {
                $checks[__('Boş alan: :list', ['list' => $empty->implode(', ')])] = false;
            }
            $add('city-guide', $m, route('city-guides.show', $m->city_id), __('Şehir rehberi: :city', ['city' => $city]), $checks);
        }

        // açılmış ama yazılmamış rehber alanları
        foreach (CityGuideSection::with(['city', 'topic'])->get() as $m) {
            if ($m->isWritten()) {
                continue;
            }
            $add('city-guide-section', $m, route('city-guides.sections.edit', [$m->city_id, $m]),
                ($m->city?->name ?? '#' . $m->city_id) . ' · ' . (self::en($m, 'title') ?: $m->topic?->getTranslation('name', 'en', false)), [
                    __('Başlık') => self::filled($m, 'title'),
                    __('Yazı') => self::filled($m, 'content'),
                ]);
        }

        return $items;
    }

    private static function en(Model $model, string $field): string
    {
        return trim((string) $model->getTranslation($field, 'en', false));
    }

    /** İngilizcesi dolu mu (HTML etiketleri ve boşluklar sayılmaz) */
    private static function filled(Model $model, string $field): bool
    {
        return trim(html_entity_decode(strip_tags(self::en($model, $field)))) !== '';
    }
}
