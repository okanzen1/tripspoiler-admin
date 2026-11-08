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

    /**
     * Birlikte tek cümle olarak çevrilen alanlar (satırlara bölünmüş başlık). Ayrı ayrı çevrilince kelime kelime
     * anlamsız olur ("KNOW BEFORE / YOU / GO" → "ÖNCE BİLİN / SEN / GİTMEK"); son alan kırmızı vurgu.
     */
    private const GROUPS = [
        HeroText::class => ['title_1', 'title_2', 'title_3', 'title_highlight'],
        SectionText::class => ['title', 'title_highlight'], // "Plan your trip in" + "three steps."
    ];

    /** Kırmızı alanı başlığın devamı olmayan bölümler (ayrı çevrilir): Hakkımızda "Hikâyemiz" alıntısı */
    private const UNGROUPED_SECTIONS = ['about_story'];

    /** @return list<string> */
    private function group(Model $model): array
    {
        if ($model instanceof SectionText && in_array($model->key, self::UNGROUPED_SECTIONS, true)) {
            return [];
        }

        return self::GROUPS[$model::class] ?? [];
    }

    /** HTML olarak çevrilen alanlar (etiketler korunur) */
    private const HTML_FIELDS = ['description', 'content'];

    public function __construct(private GoogleTranslator $google) {}

    /**
     * @param  list<string>|null  $locales  boşsa çevirisi eksik ya da eskimiş tüm aktif diller
     * @return list<string>  çevrilen diller
     */
    public function translate(Model $model, ?array $locales = null, ?callable $onLocale = null): array
    {
        $locales ??= $this->pending($model);
        $source = $this->source($model);
        $hash = $this->hash($model);

        $group = $this->group($model);

        foreach ($locales as $locale) {
            if ($group) {
                foreach ($this->translateGroup($model, $group, $locale) as $field => $value) {
                    filled($value) ? $model->setTranslation($field, $locale, $value) : $model->forgetTranslation($field, $locale);
                }
            }

            // İngilizcesi boşaltılan alanın eski çevirisi kalmasın (ör. kaldırılan 3. başlık satırı)
            foreach (array_diff($model->getTranslatableAttributes(), array_keys($source), $group) as $field) {
                $model->forgetTranslation($field, $locale);
            }

            foreach (array_diff_key($source, array_flip($group)) as $field => $value) {
                $html = in_array($field, self::HTML_FIELDS, true);
                $texts = is_array($value) ? array_values($value) : [$value];
                $translated = $this->google->translate($texts, $locale, $html);

                $model->setTranslation($field, $locale, is_array($value) ? $translated : $translated[0]);
            }

            $model->saveQuietly();
            $this->markCurrent($model, $locale, $hash);
            if ($onLocale) {
                $onLocale($locale);
            }
        }

        return $locales;
    }

    /**
     * Satırlara bölünmüş başlığı cümle cümle çevirir, sonra İngilizcedeki gibi satırlara ve kırmızı vurguya dağıtır.
     * Nokta/ünlem/soru ile biten satır bir cümleyi kapatır ("See more. / Wait less. / Travel + smarter." = 3 cümle,
     * "KNOW BEFORE / YOU + GO" = 1 cümle). Her cümlenin çevirisi kendi satırlarına uzunluğu dengeli bölünür;
     * vurgu son cümlenin son kelime(ler)i olur. Boşluksuz yazılan dillerde (Japonca, Çince) cümle tek satırda kalır.
     *
     * @param  list<string>  $fields  satır alanları, sonuncusu vurgu
     * @return array<string, string>
     */
    private function translateGroup(Model $model, array $fields, string $locale): array
    {
        $highlightField = array_pop($fields);
        $lines = array_filter(array_combine($fields, array_map(fn ($f) => trim((string) $model->getTranslation($f, Language::SOURCE, false)), $fields)), 'filled');
        $highlight = trim((string) $model->getTranslation($highlightField, Language::SOURCE, false));
        $result = array_fill_keys([...$fields, $highlightField], '');
        if (!$lines && $highlight === '') {
            return $result;
        }

        // Cümlelere ayır: [satır alanları]; vurgu son cümleye eklenir
        $sentences = [[]];
        foreach ($lines as $field => $text) {
            $sentences[array_key_last($sentences)][] = $field;
            if (preg_match('/[.!?…]$/u', $text) && $field !== array_key_last($lines)) {
                $sentences[] = [];
            }
        }

        $texts = array_map(fn ($group) => implode(' ', array_map(fn ($f) => $lines[$f], $group)), $sentences);
        $last = array_key_last($texts);
        $texts[$last] = trim($texts[$last] . ' ' . $highlight);
        $translated = $this->google->translate($texts, $locale);

        foreach ($sentences as $i => $group) {
            $text = trim($translated[$i]);
            // Kaynakta son nokta yoksa Google'ın eklediğini at
            if (!preg_match('/[.!?…]$/u', $texts[$i])) {
                $text = rtrim($text, '.。!?！？…');
            }
            $result = array_merge($result, $this->distribute($text, $group ?: [$fields[0]], $i === $last && $highlight !== '' ? $highlightField : null, $highlight));
        }

        return $result;
    }

    /**
     * Bir cümlenin çevirisini satır alanlarına (ve verilirse sondaki vurgu alanına) dağıtır.
     *
     * @param  list<string>  $lineFields
     * @return array<string, string>
     */
    private function distribute(string $text, array $lineFields, ?string $highlightField, string $highlight): array
    {
        $out = [];
        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (count($words) < 2) {
            return [$lineFields[0] => $text];
        }

        // Vurgu: İngilizcedeki kadar kelime (en az 1), satırlara en az bir kelime kalsın
        $tail = 0;
        if ($highlightField) {
            $take = min(max(1, count(preg_split('/\s+/u', $highlight))), count($words) - 1);
            $out[$highlightField] = implode(' ', array_slice($words, -$take));
            $words = array_slice($words, 0, -$take);
            $tail = mb_strlen($out[$highlightField]);
        }

        foreach ($this->balance($words, min(count($lineFields), count($words)), $tail) as $i => $line) {
            $out[$lineFields[$i]] = $line;
        }

        return $out;
    }

    /**
     * Kelimeleri sırayı bozmadan $count satıra böler; en uzun satır (son satıra vurgu da eklenir) en kısa olsun.
     *
     * @param  list<string>  $words
     * @return list<string>
     */
    private function balance(array $words, int $count, int $tailExtra = 0): array
    {
        $n = count($words);
        if ($count <= 1) {
            return [implode(' ', $words)];
        }

        $length = fn (int $from, int $to) => mb_strlen(implode(' ', array_slice($words, $from, $to - $from)));
        // best[k][i]: ilk i kelimeyi k satıra bölmenin en iyi (en uzun satır) değeri
        $best = [];
        $cut = [];
        for ($i = 1; $i <= $n; $i++) {
            $best[1][$i] = $length(0, $i);
        }
        for ($k = 2; $k <= $count; $k++) {
            for ($i = $k; $i <= $n; $i++) {
                $best[$k][$i] = PHP_INT_MAX;
                for ($j = $k - 1; $j < $i; $j++) {
                    $last = $length($j, $i) + ($k === $count && $i === $n ? $tailExtra + 1 : 0);
                    $value = max($best[$k - 1][$j], $last);
                    if ($value < $best[$k][$i]) {
                        $best[$k][$i] = $value;
                        $cut[$k][$i] = $j;
                    }
                }
            }
        }

        $lines = [];
        for ($k = $count, $i = $n; $k >= 1; $k--) {
            $j = $k === 1 ? 0 : $cut[$k][$i];
            array_unshift($lines, implode(' ', array_slice($words, $j, $i - $j)));
            $i = $j;
        }

        return $lines;
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
