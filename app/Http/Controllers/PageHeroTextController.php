<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\SectionText;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Sayfa hero'su (Aktiviteler, Şehirler, Blog): yazılar (section_texts "hero_{sayfa}", 12 dil) ve görünüm
 * (Setting "banner_{sayfa}_focus" kadraj, "banner_{sayfa}_overlay" karartma). Yazılarda :city seçili şehrin adı olur.
 */
class PageHeroTextController extends Controller
{
    /** Sayfa → alanlar: alan => [etiket, ipucu, örnek, en fazla] */
    public const FIELDS = [
        'activities' => [
            'eyebrow' => ['Üst etiket', 'Başlığın üstündeki küçük kırmızı yazı.', 'Activities', 40],
            'title' => ['Başlık', 'Büyük beyaz başlık. Altına seçili şehrin adı kırmızıyla otomatik gelir.', 'Things to do in', 60],
            'description' => ['Açıklama', 'Başlığın altındaki kısa yazı.', 'Hand-picked tickets, tours and city cards…', 260],
        ],
        'cities' => [
            'eyebrow' => ['Üst etiket', 'Şehir adının üstündeki küçük kırmızı yazı.', 'City guide', 40],
            'title' => ['Varsayılan başlık', 'Şehrin rehberinde genel giriş başlığı yoksa bu görünür. :city şehrin adı olur.', ':city travel guide', 120],
            'description' => ['Varsayılan açıklama', 'Şehrin rehberinde genel giriş açıklaması yoksa bu görünür.', 'Everything you need for :city…', 400],
        ],
        'privacy' => [
            'eyebrow' => ['Üst etiket', 'Başlığın üstündeki yeşil etiket.', 'Privacy', 40],
            'title' => ['Başlık', 'Büyük başlık.', 'Your privacy, explained simply', 80],
            'description' => ['Açıklama', 'Başlığın altındaki kısa yazı.', 'No legal jungle…', 260],
        ],
        'reviews' => [
            'eyebrow' => ['Üst etiket', 'Başlığın üstündeki sarı etiket.', 'Reviews', 40],
            'title' => ['Başlık', 'Büyük başlığın beyaz kısmı.', 'What travellers', 60],
            'title_highlight' => ['Vurgulu kısım', 'Başlığın kırmızı devamı.', 'say about us', 60],
            'description' => ['Açıklama', 'Başlığın altındaki kısa yazı.', 'Real experiences from people who planned…', 260],
        ],
        'about' => [
            'eyebrow' => ['Üst etiket', 'Başlığın üstündeki küçük kırmızı yazı.', 'About us', 40],
            'title' => ['Başlık', 'Büyük beyaz başlık.', 'We spoil your trip', 60],
            'description' => ['Açıklama', 'Solunda kırmızı çizgi olan kısa yazı.', 'TripSpoiler is an independent travel guide…', 300],
        ],
        'contact' => [
            'eyebrow' => ['Üst etiket', 'Başlığın üstündeki küçük kırmızı yazı.', 'Contact', 40],
            'title' => ['Başlık', 'Büyük beyaz başlık.', 'Let’s talk', 60],
            'description' => ['Açıklama', 'Solunda kırmızı çizgi olan kısa yazı.', 'A question about a city, an idea…', 260],
        ],
        'blog' => [
            'eyebrow' => ['Üst etiket', 'Başlığın üstündeki küçük kırmızı yazı.', 'Travel guides', 40],
            'title' => ['Alt başlık', 'Büyük şehir adının altındaki soluk yazı.', 'guides', 60],
            'description' => ['Açıklama', 'Solunda kırmızı çizgi olan kısa yazı.', 'Practical, honest articles…', 260],
        ],
    ];

    /** Fotoğraflı hero'su olan sayfalar (kadraj + karartma) */
    public const VISUAL = ['activities', 'cities', 'about', 'reviews'];

    public const OVERLAYS = ['soft', 'medium', 'strong'];

    public function edit(Page $page)
    {
        abort_unless(isset(self::FIELDS[$page->key]), 404);

        // Resimler (Aktiviteler, Şehirler): genel + şehre özel; liste satırlarında
        $spec = PageHeroController::SPECS[$page->key] ?? null;
        $banners = $spec ? \App\Models\PageBanner::where('page_key', $page->key)->get() : collect();
        $rowCities = match ($page->key) {
            'activities' => \App\Models\City::with('country')->withCount('activities')->has('activities')->orderByDesc('activities_count')->orderBy('name')->get(),
            'cities' => \App\Models\City::with(['country', 'guide', 'guideSections.topic'])->withCount('activities')->where(fn ($q) => $q->where('active', true)->orHas('activities'))->orderByDesc('activities_count')->orderBy('name')->get(),
            default => collect(),
        };

        return view('admin.pages.hero-text', [
            'page' => $page,
            'fields' => self::FIELDS[$page->key],
            'model' => SectionText::for("hero_{$page->key}"),
            'visual' => in_array($page->key, self::VISUAL, true),
            'focus' => (int) (Setting::get("banner_{$page->key}_focus") ?? 50),
            'overlay' => in_array(Setting::get("banner_{$page->key}_overlay"), self::OVERLAYS, true) ? Setting::get("banner_{$page->key}_overlay") : 'medium',
            // Blog: şehir başına kemerler (Setting "blog_hero_{şehir}" = "orta,sol,sağ" yazı id'leri; boş = sıralamaya göre)
            'archCities' => $page->key === 'blog'
                ? \App\Models\City::with(['country', 'blogs' => fn ($q) => $q->orderByRaw('sort_order = 0 desc')->orderBy('sort_order')->orderByDesc('published_at')])->whereHas('blogs')->orderBy('name')->get()
                    ->map(fn ($city) => ['city' => $city, 'values' => array_pad(explode(',', (string) Setting::get("blog_hero_{$city->id}")), 3, '')])
                : collect(),
            // Şehirler: şehir başına fotoğraf üstündeki iki kart (city_guides.hero_cards "sol,sağ"; boş = otomatik, 0 = gösterme)
            'cardCities' => $page->key === 'cities'
                ? \App\Models\City::with(['country', 'guide', 'guideSections.topic'])->whereHas('guideSections')->orderBy('name')->get()
                    ->map(fn ($city) => ['city' => $city, 'values' => array_pad(explode(',', (string) $city->guide?->hero_cards), 2, ''), 'sections' => $city->guideSections->filter->isWritten()])
                    ->filter(fn ($row) => $row['sections']->isNotEmpty())->values()
                : collect(),
            'spec' => $spec,
            'general' => $banners->firstWhere('city_id', null),
            'cityBanners' => $banners->whereNotNull('city_id')->keyBy('city_id'),
            'rowCities' => $rowCities,
            'maxMb' => SiteSettingsController::MAX_KB / 1024,
            // Yorumlar: banner'daki üç yorum kartı (Setting "reviews_hero_cards": boş = otomatik, "0" = gösterme, "1,5,3" = seçilenler)
            'reviewCards' => $page->key === 'reviews' ? \App\Models\Review::approved()->latest()->get() : collect(),
            'reviewCardsValue' => (string) Setting::get('reviews_hero_cards'),
            'preview' => \App\Services\ImageUploader::url(\App\Models\PageBanner::where('page_key', $page->key)->whereNull('city_id')->value('image') ?: Setting::get('hero_image'), 'md'),
        ]);
    }

    public function update(Request $request, Page $page)
    {
        abort_unless(isset(self::FIELDS[$page->key]), 404);
        $fields = self::FIELDS[$page->key];

        $rules = collect($fields)->mapWithKeys(fn ($f, $name) => [$name => ['nullable', 'string', 'max:' . $f[3]]])->all();
        if (in_array($page->key, self::VISUAL, true)) {
            $rules['focus'] = ['required', 'integer', 'between:0,100'];
            $rules['overlay'] = ['required', Rule::in(self::OVERLAYS)];
        }
        if ($page->key === 'cities') {
            $rules['cards'] = ['array'];
            $rules['cards.*'] = ['array', 'max:2'];
            $rules['cards.*.*'] = ['nullable', 'string', 'max:10'];
        }
        if ($page->key === 'reviews') {
            $rules['review_cards_mode'] = ['nullable', Rule::in(['auto', 'hide', 'pick'])];
            $rules['review_cards'] = ['array', 'max:3'];
            $rules['review_cards.*'] = ['nullable', 'integer'];
        }
        if ($page->key === 'blog') {
            $rules['arches'] = ['array'];
            $rules['arches.*'] = ['array', 'max:3'];
            $rules['arches.*.*'] = ['nullable', 'integer'];
        }
        $data = $request->validate($rules);

        if (isset(PageHeroController::SPECS[$page->key])) {
            app(PageHeroController::class)->saveImages($request, $page);
        }

        if ($page->key === 'reviews') {
            $ids = array_values(array_filter(array_map('intval', $data['review_cards'] ?? []), fn ($id) => \App\Models\Review::approved()->whereKey($id)->exists()));
            Setting::put('reviews_hero_cards', match ($data['review_cards_mode'] ?? 'auto') {
                'hide' => '0',
                'pick' => $ids ? implode(',', array_unique($ids)) : null,
                default => null,
            });
        }

        foreach ($data['cards'] ?? [] as $cityId => $values) {
            $city = \App\Models\City::find((int) $cityId);
            if (!$city) {
                continue;
            }
            $values = array_map(fn ($v) => $v === '0' ? '0' : ($city->guideSections()->whereKey((int) $v)->exists() ? (string) (int) $v : ''), array_pad(array_values($values), 2, ''));
            \App\Models\CityGuide::firstOrNew(['city_id' => $city->id])->fill(['hero_cards' => array_filter($values, 'strlen') ? implode(',', $values) : null])->save();
        }

        foreach ($data['arches'] ?? [] as $cityId => $ids) {
            $ids = array_map(fn ($id) => \App\Models\Blog::where('city_id', (int) $cityId)->whereKey((int) $id)->exists() ? (string) (int) $id : '', array_pad(array_values($ids), 3, ''));
            Setting::put('blog_hero_' . (int) $cityId, array_filter($ids, 'strlen') ? implode(',', $ids) : null);
        }

        $record = SectionText::for("hero_{$page->key}");
        foreach (array_keys($fields) as $field) {
            filled($data[$field] ?? null) ? $record->setTranslation($field, 'en', trim($data[$field])) : $record->forgetTranslation($field, 'en');
        }
        $record->save();

        if (isset($data['focus'])) {
            Setting::put("banner_{$page->key}_focus", (string) $data['focus']);
            Setting::put("banner_{$page->key}_overlay", $data['overlay']);
        }

        return back()->with('success', __(':page hero ayarları kaydedildi. Boş bıraktığın yazıda varsayılan metin görünür.', ['page' => $page->name]));
    }
}
