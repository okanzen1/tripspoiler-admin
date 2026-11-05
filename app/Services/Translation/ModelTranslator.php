<?php

namespace App\Services\Translation;

use App\Models\Activity;
use App\Models\Announcement;
use App\Models\Blog;
use App\Models\Faq;
use App\Models\Feature;
use App\Models\HeroText;
use App\Models\Language;
use App\Models\Page;
use App\Models\SectionText;
use App\Models\Step;
use App\Models\TranslationState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Bir kaydın çok dilli alanlarını İngilizceden diğer dillere çevirir ve hangi İngilizce metinden
 * çevrildiğini (translation_states) kaydeder. Slug çevrilmez: her dilde aynı adres kullanılır.
 *
 * Durumlar: ok = güncel, stale = İngilizce sonradan değişti ya da kaynağı bilinmiyor (eski sistem), missing = çeviri yok.
 */
class ModelTranslator
{
    /** Paneldeki tür adı → model */
    public const TYPES = [
        'activity' => Activity::class,
        'blog' => Blog::class,
        'faq' => Faq::class,
        'page' => Page::class,
        'announcement' => Announcement::class,
        'hero' => HeroText::class,
        'feature' => Feature::class,
        'section' => SectionText::class,
        'step' => Step::class,
        'city-guide' => \App\Models\CityGuide::class,
        'city-guide-section' => \App\Models\CityGuideSection::class,
        'city-guide-topic' => \App\Models\CityGuideTopic::class,
    ];

    /** HTML olarak çevrilen alanlar (etiketler korunur) */
    private const HTML_FIELDS = ['description', 'content'];

    public function __construct(private GoogleTranslator $google) {}

    /**
     * @param  list<string>|null  $locales  boşsa çevirisi eksik ya da eskimiş tüm aktif diller
     * @return list<string>  çevrilen diller
     */
    public function translate(Model $model, ?array $locales = null): array
    {
        $locales ??= $this->pending($model);
        $source = $this->source($model);
        $hash = $this->hash($model);

        foreach ($locales as $locale) {
            foreach ($source as $field => $value) {
                $html = in_array($field, self::HTML_FIELDS, true);
                $texts = is_array($value) ? array_values($value) : [$value];
                $translated = $this->google->translate($texts, $locale, $html);

                $model->setTranslation($field, $locale, is_array($value) ? $translated : $translated[0]);
            }

            $model->saveQuietly();
            $this->markCurrent($model, $locale, $hash);
        }

        return $locales;
    }

    /**
     * Bu dildeki çeviri şu anki İngilizceye göre güncel sayılır (otomatik çeviriden ya da elle düzeltmeden sonra).
     */
    public function markCurrent(Model $model, string $locale, ?string $hash = null): void
    {
        TranslationState::updateOrCreate(
            ['translatable_type' => $model->getMorphClass(), 'translatable_id' => $model->getKey(), 'locale' => $locale],
            ['source_hash' => $hash ?? $this->hash($model), 'translated_at' => now()],
        );
    }

    /**
     * Elle düzenlenecek alanlar: alan → [İngilizcesi, bu dildeki hali, HTML mi]
     *
     * @return array<string, array{source: string|list<string>, value: string|list<string>|null, html: bool}>
     */
    public function fields(Model $model, string $locale): array
    {
        return collect($this->source($model))->map(fn ($value, $field) => [
            'source' => $value,
            'value' => $model->getTranslation($field, $locale, false) ?: null,
            'html' => in_array($field, self::HTML_FIELDS, true),
        ])->all();
    }

    /**
     * Çeviri düzenleme ekranından dönülecek kayıt sayfası ve başlığı.
     *
     * @return array{url: string, title: string}
     */
    public static function owner(string $type, Model $model): array
    {
        return match ($type) {
            'activity' => ['url' => route('activities.edit', $model), 'title' => $model->getTranslation('name', 'en')],
            'blog' => ['url' => route('blogs.edit', $model), 'title' => $model->getTranslation('title', 'en')],
            'faq' => ['url' => route('faqs.edit', $model), 'title' => $model->getTranslation('question', 'en')],
            'page' => ['url' => route('pages.seo', $model), 'title' => $model->name . ' · SEO'],
            'announcement' => ['url' => route('announcements.edit', $model), 'title' => $model->getTranslation('text', 'en')],
            'section' => ($contentKey = collect(\App\Http\Controllers\PageContentController::SECTIONS)->search(fn ($secs) => collect($secs)->contains(fn ($d) => $d[3] === $model->key || in_array($model->key, $d[4], true))))
                    && ($contentPage = Page::where('key', $contentKey)->first())
                ? ['url' => route('pages.content.section', [$contentPage, collect(\App\Http\Controllers\PageContentController::SECTIONS[$contentKey])->search(fn ($d) => $d[3] === $model->key || in_array($model->key, $d[4], true))]), 'title' => $contentPage->name . ' · ' . __('Sayfa içeriği')]
                : (str_starts_with((string) $model->key, 'hero_') && ($heroPage = Page::where('key', substr($model->key, 5))->first())
                ? ['url' => route('pages.hero-text', $heroPage), 'title' => $heroPage->name . ' · ' . __('Hero yazıları')]
                : match ($model->key) {
                'blog' => ['url' => route('blog-section.index'), 'title' => __('Blog bölümü: başlık')],
                'newsletter' => ['url' => route('blog-section.index'), 'title' => __('Blog bölümü: bülten kutusu')],
                'intro', 'social', 'reviews', 'city_entry', 'partners' => ['url' => route('section-texts.edit', $model->key), 'title' => __(\App\Http\Controllers\SectionTextController::SECTIONS[$model->key][0])],
                default => ['url' => route('steps.index'), 'title' => __('Nasıl çalışır: bölüm yazıları')],
            }),
            'step' => ['url' => route('steps.edit', $model), 'title' => $model->getTranslation('title', 'en')],
            'feature' => ['url' => route('features.edit', $model), 'title' => $model->getTranslation('title', 'en')],
            'hero' => ['url' => route('site-settings.hero') . '#adim-yazi', 'title' => __('Hero alanı yazıları')],
            'city-guide' => ['url' => route('city-guides.show', $model->city_id), 'title' => __('Şehir rehberi: :city', ['city' => $model->city?->name])],
            'city-guide-section' => ['url' => route('city-guides.sections.edit', [$model->city_id, $model]), 'title' => $model->city?->name . ' · ' . ($model->getTranslation('title', 'en', false) ?: $model->topic?->getTranslation('name', 'en'))],
            'city-guide-topic' => ['url' => route('city-guide-topics.edit', $model), 'title' => __('Rehber alanı: :name', ['name' => $model->getTranslation('name', 'en')])],
        };
    }

    /**
     * Dil kodu → ok | stale | missing (kaynak dil hariç aktif diller)
     *
     * @return array<string, string>
     */
    public function status(Model $model, ?Collection $states = null): array
    {
        $hash = $this->hash($model);
        $states ??= TranslationState::where('translatable_type', $model->getMorphClass())->where('translatable_id', $model->getKey())->pluck('source_hash', 'locale');
        $main = array_key_first($this->source($model));

        return Language::targets()->mapWithKeys(fn (Language $language) => [$language->code => match (true) {
            $main === null || filled($states[$language->code] ?? null) && $states[$language->code] === $hash => 'ok',
            blank($model->getTranslation($main, $language->code, false)) => 'missing',
            default => 'stale',
        }])->all();
    }

    /**
     * Çevirisi eksik ya da eskimiş diller.
     *
     * @return list<string>
     */
    public function pending(Model $model): array
    {
        return array_keys(array_filter($this->status($model), fn ($state) => $state !== 'ok'));
    }

    /**
     * İngilizce (kaynak) değerler: boş olmayan çevrilebilir alanlar.
     *
     * @return array<string, string|list<string>>
     */
    private function source(Model $model): array
    {
        return collect($model->getTranslatableAttributes())
            ->mapWithKeys(fn ($field) => [$field => $model->getTranslation($field, Language::SOURCE, false)])
            ->filter(fn ($value) => is_array($value) ? $value !== [] : filled($value))
            ->all();
    }

    private function hash(Model $model): string
    {
        return hash('sha256', json_encode($this->source($model), JSON_UNESCAPED_UNICODE));
    }
}
