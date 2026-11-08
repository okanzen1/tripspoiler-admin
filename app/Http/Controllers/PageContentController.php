<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\SectionText;
use App\Services\Translation\ModelTranslator;
use App\Support\TranslationJobs;
use Illuminate\Http\Request;

use function Illuminate\Support\defer;

/**
 * Sayfa içeriği (şimdilik Hakkımızda): sayfadaki bölümlerin yazıları. Her blok bir section_texts kaydı
 * ("about_story" …); alanlar eyebrow / title / title_highlight / description. İngilizce yazılır, tek düğmeyle 12 dile çevrilir.
 */
class PageContentController extends Controller
{
    /**
     * Sayfa → bloklar: anahtar => [panelde ad, açıklama, [alan => [etiket, örnek, en fazla]]]
     */
    public const BLOCKS = [
        'privacy' => [
            'privacy_who' => ['Biz kimiz', 'Gizlilik sayfasındaki bölüm.', ['title' => ['Başlık', 'Biz kimiz', 90], 'description' => ['Metin ("- " ile başlayan satır liste olur)', '...', 3000]]],
            'privacy_collect' => ['Neleri topluyoruz', 'Gizlilik sayfasındaki bölüm.', ['title' => ['Başlık', 'Neleri topluyoruz', 90], 'description' => ['Metin ("- " ile başlayan satır liste olur)', '...', 3000]]],
            'privacy_use' => ['Nasıl kullanıyoruz', 'Gizlilik sayfasındaki bölüm.', ['title' => ['Başlık', 'Nasıl kullanıyoruz', 90], 'description' => ['Metin ("- " ile başlayan satır liste olur)', '...', 3000]]],
            'privacy_cookies' => ['Çerezler', 'Gizlilik sayfasındaki bölüm.', ['title' => ['Başlık', 'Çerezler', 90], 'description' => ['Metin ("- " ile başlayan satır liste olur)', '...', 3000]]],
            'privacy_partners' => ['Partner bağlantıları', 'Gizlilik sayfasındaki bölüm.', ['title' => ['Başlık', 'Partner bağlantıları', 90], 'description' => ['Metin ("- " ile başlayan satır liste olur)', '...', 3000]]],
            'privacy_storage' => ['Saklama ve güvenlik', 'Gizlilik sayfasındaki bölüm.', ['title' => ['Başlık', 'Saklama ve güvenlik', 90], 'description' => ['Metin ("- " ile başlayan satır liste olur)', '...', 3000]]],
            'privacy_rights' => ['Haklar', 'Gizlilik sayfasındaki bölüm.', ['title' => ['Başlık', 'Haklar', 90], 'description' => ['Metin ("- " ile başlayan satır liste olur)', '...', 3000]]],
            'privacy_changes' => ['Değişiklikler', 'Gizlilik sayfasındaki bölüm.', ['title' => ['Başlık', 'Değişiklikler', 90], 'description' => ['Metin ("- " ile başlayan satır liste olur)', '...', 3000]]],
        ],
        'reviews' => [
            'reviews_list' => ['Liste başlığı', 'Yorum listesinin üstündeki başlık.', ['title' => ['Başlık', 'Latest reviews', 60]]],
            'reviews_share' => ['Yorum çağrısı', 'Soldaki kırmızı kutu.', [
                'title' => ['Başlık', 'Planned a trip with us?', 80],
                'description' => ['Metin', 'Tell other travellers how it went…', 200],
            ]],
        ],
        'about' => [
            'about_story' => ['Hikâyemiz', 'Başlığın altında iki paragraf ve bir alıntı.', [
                'eyebrow' => ['Üst etiket', 'Our story', 40],
                'title' => ['Başlık', 'Too many tabs, too little time', 90],
                'description' => ['Metin', 'We started TripSpoiler after… (iki paragraf için arada boş satır bırak)', 2000],
                'title_highlight' => ['Alıntı', '“The best trip is the one you can plan in an evening…”', 200],
            ]],
            'about_how' => ['Nasıl çalışır: başlık', 'Üç adım kartının üstündeki başlık.', [
                'eyebrow' => ['Üst etiket', 'How it works', 40],
                'title' => ['Başlık', 'Simple and transparent', 90],
            ]],
            'about_how_1' => ['Adım 1', 'İlk kart.', ['title' => ['Başlık', 'Read the guide', 60], 'description' => ['Metin', 'City guides explain each sight…', 260]]],
            'about_how_2' => ['Adım 2', 'İkinci kart.', ['title' => ['Başlık', 'Compare the options', 60], 'description' => ['Metin', 'Tickets, tours and city cards side by side…', 260]]],
            'about_how_3' => ['Adım 3', 'Üçüncü kart.', ['title' => ['Başlık', 'Book with the partner', 60], 'description' => ['Metin', 'You book and pay on the partner’s site…', 260]]],
            'about_values' => ['İlkeler: başlık', 'Dört ilke kartının üstündeki başlık.', [
                'eyebrow' => ['Üst etiket', 'What we believe', 40],
                'title' => ['Başlık', 'Our principles', 90],
            ]],
            'about_value_1' => ['İlke 1', 'Kırmızı kart.', ['title' => ['Başlık', 'Honest', 40], 'description' => ['Metin', 'We write what we would tell a friend…', 200]]],
            'about_value_2' => ['İlke 2', 'Turuncu kart.', ['title' => ['Başlık', 'Clear', 40], 'description' => ['Metin', 'No jargon, no endless lists…', 200]]],
            'about_value_3' => ['İlke 3', 'Yeşil kart.', ['title' => ['Başlık', 'Up to date', 40], 'description' => ['Metin', 'Guides are reviewed regularly…', 200]]],
            'about_value_4' => ['İlke 4', 'Mavi kart.', ['title' => ['Başlık', 'Independent', 40], 'description' => ['Metin', 'We recommend what is good for travellers…', 200]]],
            'about_cta' => ['Kapanış', 'Sayfanın sonundaki koyu kutu ve iki düğme.', [
                'title' => ['Başlık', 'Ready for your next city?', 90],
                'description' => ['Metin', 'Browse the activities or ask us anything…', 260],
            ]],
        ],
    ];

    /**
     * Panelde bölümler: bölüm => [ad, açıklama, ikon, üst blok, [kart blokları]] (kartlar yan yana düzenlenir)
     */
    public const SECTIONS = [
        'privacy' => [
            'privacy_who' => ['Biz kimiz', 'Başlık ve metin.', 'shield', 'privacy_who', []],
            'privacy_collect' => ['Neleri topluyoruz', 'Başlık ve metin.', 'shield', 'privacy_collect', []],
            'privacy_use' => ['Nasıl kullanıyoruz', 'Başlık ve metin.', 'shield', 'privacy_use', []],
            'privacy_cookies' => ['Çerezler', 'Başlık ve metin.', 'shield', 'privacy_cookies', []],
            'privacy_partners' => ['Partner bağlantıları', 'Başlık ve metin.', 'shield', 'privacy_partners', []],
            'privacy_storage' => ['Saklama ve güvenlik', 'Başlık ve metin.', 'shield', 'privacy_storage', []],
            'privacy_rights' => ['Haklar', 'Başlık ve metin.', 'shield', 'privacy_rights', []],
            'privacy_changes' => ['Değişiklikler', 'Başlık ve metin.', 'shield', 'privacy_changes', []],
        ],
        'reviews' => [
            'reviews_list' => ['Yorum listesi', 'Listenin başlığı.', 'message', 'reviews_list', []],
            'reviews_share' => ['Yorum çağrısı kutusu', 'Soldaki kırmızı “Yorum yaz” kutusu.', 'heart', 'reviews_share', []],
        ],
        'about' => [
            'about_story' => ['Hikâyemiz', 'Başlık, iki paragraf ve bir alıntı.', 'book', 'about_story', []],
            'about_how' => ['Nasıl çalışır', 'Başlık ve üç adım kartı.', 'route', 'about_how', ['about_how_1', 'about_how_2', 'about_how_3']],
            'about_values' => ['İlkelerimiz', 'Başlık ve dört ilke kartı.', 'heart', 'about_values', ['about_value_1', 'about_value_2', 'about_value_3', 'about_value_4']],
            'about_cta' => ['Kapanış', 'Sayfanın sonundaki kırmızı kutu.', 'sparkles', 'about_cta', []],
        ],
    ];

    public function __construct(private ModelTranslator $translator) {}

    /** Liste: her bölüm bir satır (durum, sitede açık mı, Düzenle) */
    public function edit(Page $page)
    {
        $sections = self::SECTIONS[$page->key] ?? abort(404);
        $config = \App\Support\PageSections::config($page->key);

        $rows = collect($sections)->map(function ($section, $key) use ($config) {
            $records = collect([$section[3], ...$section[4]])->map(fn ($k) => SectionText::for($k));
            $written = $records->filter(fn ($r) => filled($r->title));

            return [
                'key' => $key, 'name' => $section[0], 'hint' => $section[1], 'icon' => $section[2],
                'title' => $records->first()->getTranslation('title', 'en', false),
                'written' => $written->count(), 'total' => $records->count(),
                'pending' => $written->sum(fn ($r) => count($this->translator->pending($r))),
                // bölüm listesinde olmayan (sabit) içerikler her zaman açık
                'on' => !in_array($key, $config['order'], true) || in_array($key, $config['on'], true),
                'position' => array_search($key, $config['order'], true),
            ];
        })->sortBy('position')->values();

        return view('admin.pages.content', compact('page', 'rows'));
    }

    /** Tek bölümün düzenleme ekranı */
    public function section(Page $page, string $section)
    {
        $def = self::SECTIONS[$page->key][$section] ?? abort(404);
        $blocks = self::BLOCKS[$page->key];

        return view('admin.pages.content-section', [
            'page' => $page, 'key' => $section, 'def' => $def,
            'head' => [$def[3], $blocks[$def[3]][2], SectionText::for($def[3])],
            'cards' => collect($def[4])->map(fn ($k) => [$k, $blocks[$k][0], $blocks[$k][2], SectionText::for($k)]),
            'pending' => collect([$def[3], ...$def[4]])->map(fn ($k) => SectionText::for($k))->filter(fn ($r) => filled($r->title))->sum(fn ($r) => count($this->translator->pending($r))),
        ]);
    }

    public function update(Request $request, Page $page, string $section)
    {
        $def = self::SECTIONS[$page->key][$section] ?? abort(404);
        $blocks = array_intersect_key(self::BLOCKS[$page->key], array_flip([$def[3], ...$def[4]]));

        $rules = [];
        foreach ($blocks as $key => [, , $fields]) {
            foreach ($fields as $field => [, , $max]) {
                $rules["{$key}.{$field}"] = ['nullable', 'string', "max:{$max}"];
            }
        }
        $data = $request->validate($rules);

        foreach ($blocks as $key => [, , $fields]) {
            $record = SectionText::for($key);
            foreach (array_keys($fields) as $field) {
                $value = trim((string) ($data[$key][$field] ?? ''));
                filled($value) ? $record->setTranslation($field, 'en', $value) : $record->forgetTranslation($field, 'en');
            }
            $record->save();
        }

        return back()->with('success', __(':name kaydedildi. Diğer dillere çevirmek için “Tüm dillere çevir”e bas.', ['name' => __($def[0])]));
    }

    /** Bölümün bütün yazılarını eksik/eskimiş dillere çevirir */
    /**
     * Bölümün (başlık + kartlar) eksik/eskimiş dillerini çevirir. Panel (fetch) arka planda çevirtir: hemen döner,
     * çeviri cevaptan sonra sürer, ilerleme sağ üstteki bildirimde (TranslationJobs). Sıraya (kuyruk) atılmaz:
     * canlıda kuyruk işçisi olmayınca çeviri hiç yapılmıyordu.
     */
    public function translate(Request $request, Page $page, string $section)
    {
        $def = self::SECTIONS[$page->key][$section] ?? abort(404);
        $work = collect([$def[3], ...$def[4]])
            ->map(fn ($key) => SectionText::for($key))
            ->filter(fn ($record) => filled($record->title))
            ->map(fn ($record) => [$record, $this->translator->pending($record)])
            ->filter(fn ($item) => $item[1] !== [])
            ->values();

        if ($work->isEmpty()) {
            return $request->expectsJson()
                ? response()->json(['message' => __('Tüm çeviriler zaten güncel.')])
                : back()->with('success', __('Tüm çeviriler zaten güncel.'));
        }

        $run = function (?string $jobKey = null) use ($work, $page, $section, $def) {
            $done = [];
            foreach ($work as [$record, $locales]) {
                $done = [...$done, ...$this->translator->translate($record, $locales, fn (string $locale) => $jobKey && TranslationJobs::progress($jobKey, $locale))];
            }
            $names = \App\Models\Language::whereIn('code', array_unique($done))->pluck('native_name')->implode(', ');
            \App\Support\ActivityLogger::write('translated', 'section_text', null, $page->name . ' · ' . __($def[0]), ['Diller' => [null, $names]]);

            return $names;
        };

        if (!$request->expectsJson()) {
            set_time_limit(900);
            $names = $run();

            return back()->with('success', __('Çevrildi: :languages', ['languages' => $names]));
        }

        $key = "content:{$page->id}:{$section}";
        if (TranslationJobs::running($key)) {
            return response()->json(['job' => TranslationJobs::get($key)]);
        }
        // ilerleme: her (kayıt, dil) çifti bir adım
        $steps = $work->flatMap(fn ($item) => $item[1])->values()->all();
        $job = TranslationJobs::start($key, $page->name . ' · ' . __($def[0]), url()->previous(), $steps);

        ignore_user_abort(true);
        defer(function () use ($run, $key) {
            ignore_user_abort(true);
            set_time_limit(900);
            try {
                TranslationJobs::finish($key, __('Çevrildi: :languages', ['languages' => $run($key)]));
            } catch (\Throwable $e) {
                report($e);
                TranslationJobs::fail($key, __('Çeviri yapılamadı: :error', ['error' => $e->getMessage()]));
            }
        });

        return response()->json(['job' => $job]);
    }
}
