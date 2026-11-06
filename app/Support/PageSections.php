<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Sayfa bölümleri (anasayfa hariç): her sayfa için sıra ve aç/kapa (Setting "page_sections_{sayfa}").
 * Değer: {"order": ["reviews", …], "on": ["reviews", "partners", …]}. Site aynı anahtarı okur
 * (tripspoiler/app/Support/PageSections.php); sabitler iki tarafta aynı olmalı.
 */
class PageSections
{
    /** Sırası değiştirilebilen içerik bölümleri: [ad, açıklama, şehre özel mi] */
    public const CONTENT = [
        'popular' => ['Popüler aktiviteler', 'Seçili şehrin popüler işaretli aktiviteleri. Şehirde yoksa görünmez.', true],
        'blog' => ['Blog yazıları', 'Seçili şehrin en yeni 3 yazısı ve e-posta aboneliği. Şehirde yazı yoksa görünmez.', true],
        'city_entry' => ['Şehirler çağrısı', 'Büyük resimli kart. Seçili şehrin banner resmi ve "o şehri keşfet" düğmesiyle.', true],
        'faq' => ['Sık sorulan sorular', 'Bu sayfaya ve seçili şehre yazılmış sorular (SSS › Nerede görünecek). Soru yoksa görünmez.', true],
        'reviews' => ['Yorumlar', 'Ziyaretçi yorumları ve puan özeti. Tüm şehirler için aynı.', false],
        'social' => ['Sosyal medya', 'Instagram ve TikTok bölümü.', false],
        'steps' => ['Nasıl çalışır', 'Adım adım anlatım kartları.', false],
        'intro' => ['TripSpoiler tanıtımı', 'Kısa tanıtım ve sitedeki sayılar.', false],
    ];

    /** Sayfanın ana içeriği de sıralamaya girer (her zaman açık, kapatılamaz): sayfa => [ad, açıklama] */
    public const MAIN = [
        'activities' => ['Aktivite listesi', 'Sayfanın asıl içeriği: filtreler, arama ve aktivite kartları. Kapatılamaz, yeri değiştirilebilir.'],
        'reviews' => ['Yorum listesi', 'Puan özeti, yıldız süzgeci ve yorumlar. Kapatılamaz, yeri değiştirilebilir.'],
        'activity' => ['Aktivite içeriği', 'Galeri, bilgiler, açıklama ve "Uygunluğu kontrol et" kutusu. Kapatılamaz, yeri değiştirilebilir.'],
    ];

    /** Yeri sabit, sadece aç/kapa */
    public const FIXED = [
        'announcements' => ['Duyuru şeridi', 'Sayfanın en üstündeki kayan yazı.'],
        'partners' => ['Partner logoları', 'Footer\'ın üstündeki kayan logolar.'],
    ];

    /** Ayar yapılmamışsa açık olan içerik bölümleri (sitedeki varsayılanla aynı) */
    public const DEFAULTS = [
        'activities' => ['reviews', 'social'],
        'activity' => ['reviews', 'faq'],
        'blog' => ['popular', 'reviews', 'social'],
        'cities' => ['popular', 'blog', 'reviews'],
        'reviews' => ['social'],
        'about' => [],
        'contact' => [],
        'privacy' => [],
    ];

    /**
     * Aktivite detayında bölümler şehre göre değil, o aktiviteye göre dolar: ad ve açıklamalar farklı.
     */
    public const OVERRIDES = [
        'activity' => [
            'popular' => ['Benzer aktiviteler', 'Aynı şehirdeki diğer aktiviteler (bu aktivite hariç).', false],
            'blog' => ['Blog yazıları', 'Bu aktiviteye bağlanan yazılar; yoksa şehrin yazıları. Yazı yoksa görünmez.', false],
            'city_entry' => ['Şehirler çağrısı', 'Büyük resimli kart; aktivitenin şehrine göre.', false],
            'faq' => ['Sık sorulan sorular', 'Bu aktiviteye yazılmış sorular (SSS › Bir aktivitenin sayfası). Soru yoksa görünmez.', false],
            'reviews' => ['Yorumlar', 'Bu aktiviteye yapılan yorumlar ve puan özeti.', false],
        ],
    ];

    /**
     * Sayfaya göre bölüm adları ve açıklamaları.
     *
     * @return array<string, array{0: string, 1: string, 2: bool}>
     */
    /** Şehirle ilgisi olmayan sayfalar: şehre bağlı bölümler (popüler, blog, şehirler çağrısı) yok, SSS sadece sayfanın */
    public const NO_CITY = ['contact', 'about', 'privacy', 'reviews'];

    public const CITY_ONLY = ['popular', 'blog', 'city_entry'];

    public static function labels(string $page): array
    {
        $labels = array_replace(self::CONTENT, self::OVERRIDES[$page] ?? [], self::guideLabels($page));

        // Yorumlar sayfasında "Yorumlar" bölümü tekrar olur
        if ($page === 'reviews') {
            unset($labels['reviews']);
        }

        // sayfanın kendi "Nasıl çalışır"ı varsa anasayfadaki kartlarla karışmasın
        if (isset(self::EXTRA[$page]['about_how'])) {
            $labels['steps'] = [__('Nasıl çalışır (anasayfadaki kartlar)'), __('Anasayfadaki adım adım anlatım kartlarının aynısı.'), false];
        }

        if (in_array($page, self::NO_CITY, true)) {
            $labels = array_diff_key($labels, array_flip(self::CITY_ONLY));
            $labels['faq'] = ['Sık sorulan sorular', 'Bu sayfaya yazılmış sorular (SSS › Nerede görünecek › Bir sayfa). Soru yoksa görünmez.', false];
        }

        return $labels;
    }

    /**
     * Şehirler sayfasında şehir rehberi alanları da bölüm olarak sıralanır: anahtar "guide:{id}".
     * Yeni eklenen alan listeye kendiliğinden girer (açık, en sonda).
     *
     * @return array<string, array{0: string, 1: string, 2: bool}>
     */
    /**
     * Sayfaya özel içerik bölümleri (yazıları Sayfalar › sayfa › Sayfa içeriği'nde); yeni sayfada açık başlar.
     * Site de aynı anahtarları bilir (tripspoiler/app/Support/PageSections.php EXTRA).
     */
    public const EXTRA = [
        'about' => [
            'about_story' => ['Hikâyemiz', 'Başlık, iki paragraf ve alıntı. Yazıları: Sayfa içeriği.', false],
            'about_how' => ['Nasıl çalışır', 'Başlık ve üç adım kartı. Yazıları: Sayfa içeriği.', false],
            'about_values' => ['İlkelerimiz', 'Başlık ve dört ilke kartı. Yazıları: Sayfa içeriği.', false],
            'about_cta' => ['Kapanış', 'Kırmızı kutu ve iki düğme. Yazıları: Sayfa içeriği.', false],
        ],
        'privacy' => [
            'privacy_who' => ['Biz kimiz', 'Gizlilik sayfasındaki bölüm. Yazıları: Sayfa içeriği.', false],
            'privacy_collect' => ['Neleri topluyoruz', 'Gizlilik sayfasındaki bölüm. Yazıları: Sayfa içeriği.', false],
            'privacy_use' => ['Nasıl kullanıyoruz', 'Gizlilik sayfasındaki bölüm. Yazıları: Sayfa içeriği.', false],
            'privacy_cookies' => ['Çerezler', 'Gizlilik sayfasındaki bölüm. Yazıları: Sayfa içeriği.', false],
            'privacy_partners' => ['Partner bağlantıları', 'Gizlilik sayfasındaki bölüm. Yazıları: Sayfa içeriği.', false],
            'privacy_storage' => ['Saklama ve güvenlik', 'Gizlilik sayfasındaki bölüm. Yazıları: Sayfa içeriği.', false],
            'privacy_rights' => ['Haklar', 'Gizlilik sayfasındaki bölüm. Yazıları: Sayfa içeriği.', false],
            'privacy_changes' => ['Değişiklikler', 'Gizlilik sayfasındaki bölüm. Yazıları: Sayfa içeriği.', false],
        ],
    ];

    public static function guideLabels(string $page): array
    {
        if (isset(self::EXTRA[$page])) {
            return array_map(fn ($l) => [__($l[0]), __($l[1]), $l[2]], self::EXTRA[$page]);
        }
        if ($page !== 'cities') {
            return [];
        }

        return \App\Models\CityGuideTopic::orderBy('sort_order')->orderBy('id')->get()
            ->mapWithKeys(fn ($topic) => ['guide:' . $topic->id => [
                $topic->getTranslation('name', 'tr', false) ?: $topic->name,
                __('Şehir rehberi alanı: her şehir kendi yazısını yazar (Şehir rehberi). O şehirde yazılmamışsa ya da kapalıysa görünmez.'),
                true,
            ]])->all();
    }

    public static function supports(string $page): bool
    {
        return array_key_exists($page, self::DEFAULTS);
    }

    /** @return array{order: list<string>, on: list<string>} */
    public static function config(string $page): array
    {
        $saved = json_decode((string) Setting::get("page_sections_{$page}"), true);
        $defaults = self::DEFAULTS[$page] ?? [];
        $guide = array_keys(self::guideLabels($page));
        $keys = array_values(array_diff([...array_keys(self::CONTENT), ...$guide], in_array($page, self::NO_CITY, true) ? self::CITY_ONLY : [], $page === 'reviews' ? ['reviews'] : []));
        $main = isset(self::MAIN[$page]) ? ['main'] : [];

        $order = array_values(array_intersect($saved['order'] ?? [], [...$main, ...$keys]));
        // Ana içerik varsayılan olarak en üstte (sitedeki ile aynı); rehber alanları varsayılanda içerikten önce
        $order = array_values(array_unique([...(in_array('main', $order, true) ? [] : $main), ...$order, ...$guide, ...$defaults, ...$keys]));
        $on = is_array($saved['on'] ?? null) ? $saved['on'] : [...$defaults, ...array_keys(self::FIXED)];
        // Kaydedilmiş sırada hiç olmayan rehber alanı yenidir: açık başlar
        $on = [...$on, ...array_diff($guide, $saved['order'] ?? [])];

        return ['order' => $order, 'on' => array_values(array_unique($on))];
    }

    /**
     * @param  list<string>  $order
     * @param  list<string>  $on
     */
    public static function save(string $page, array $order, array $on): void
    {
        $content = [...array_keys(self::CONTENT), ...array_keys(self::guideLabels($page))];
        $main = isset(self::MAIN[$page]) ? ['main'] : [];
        Setting::put("page_sections_{$page}", json_encode([
            'order' => array_values(array_intersect($order, [...$main, ...$content])),
            'on' => array_values(array_intersect($on, [...$content, ...array_keys(self::FIXED)])),
        ]));
    }

    /**
     * Şehirler sayfasında açık rehber alanlarının sırası (topic id'leri).
     *
     * @return list<int>
     */
    public static function guideOrder(): array
    {
        $config = self::config('cities');

        return array_values(array_map(fn ($key) => (int) substr($key, 6), array_filter($config['order'], fn ($key) => str_starts_with($key, 'guide:'))));
    }

    /** Rehber alanı şehirler sayfasında açık mı */
    public static function guideOn(int $topicId): bool
    {
        return in_array('guide:' . $topicId, self::config('cities')['on'], true);
    }
}
