<?php

namespace App\Support;

use App\Models\Blog;
use App\Models\City;
use App\Models\CityGuideTopic;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Str;

/**
 * "Hazır yazı yapıştır": Claude / ChatGPT'nin verdiği HTML'den blog yazıları ya da şehir rehberleri çıkarır.
 * Tek yapıştırmada birden fazla yazı (<post>) ya da şehir (<guide data-city="…">) gelebilir.
 * Resimlere dokunmaz (gelen <img>'ler HtmlSanitizer'da düşer); resimleri kullanıcı panelden ekler.
 *
 * Blog biçimi (her yazı bir <post>; tek yazıda <post> şart değil):
 *   <post><title>…</title> <meta name="kind|excerpt|seo-title|seo-description|tags|slug" content="…">  (kind: guide | news)
 *   <article>…yazı…</article> <section class="faq"><h3>Soru</h3><p>Cevap</p>…</section></post>
 *
 * Şehir rehberi biçimi (her şehir bir <guide>; şehir sayfasından tek şehirde <guide> şart değil):
 *   <guide data-city="Istanbul"><header><h1>Genel başlık</h1><p>Genel açıklama</p></header>
 *   <section data-topic="sights"><h2>Alan başlığı</h2>…yazı…</section> …</guide>
 */
class ContentImport
{
    /**
     * @return list<array{title: ?string, slug: ?string, kind: ?string, excerpt: ?string, meta_title: ?string, meta_description: ?string, tags: list<string>, content: ?string, faqs: list<array{question: string, answer: string}>}>
     */
    public static function blogs(string $html): array
    {
        [, $xp] = self::load($html);
        $posts = iterator_to_array($xp->query('//post'));

        return array_map(fn (DOMNode $scope) => self::blog($xp, $scope), $posts ?: [$xp->query('//body')->item(0)]);
    }

    /**
     * @return list<array{city: ?string, title: ?string, description: ?string, sections: array<string, array{title: string, content: ?string}>, unknown: list<string>}>
     */
    public static function cityGuides(string $html): array
    {
        [, $xp] = self::load($html);
        $keys = CityGuideTopic::pluck('key')->all();
        $guides = iterator_to_array($xp->query('//guide'));

        return array_map(
            fn (DOMNode $scope) => ['city' => $scope instanceof DOMElement && $scope->tagName === 'guide' ? (trim($scope->getAttribute('data-city')) ?: null) : null] + self::cityGuide($xp, $scope, $keys),
            $guides ?: [$xp->query('//body')->item(0)],
        );
    }

    /**
     * Yazılan şehir adını paneldeki şehirle eşleştirir (ad ya da adres; büyük/küçük harf ve Türkçe karakter fark etmez).
     */
    public static function findCity(?string $name): ?City
    {
        $key = Str::slug((string) $name);

        return $key === '' ? null : City::all()->first(fn (City $c) => $c->slug === $key || Str::slug($c->name) === $key);
    }

    /**
     * Kopyalanıp Claude'a / ChatGPT'ye verilecek talimat (blog).
     */
    public static function blogPrompt(): string
    {
        $existing = Blog::latest('id')->limit(150)->get()->map(fn (Blog $b) => '- ' . $b->getTranslation('title', 'en', false))->filter(fn ($t) => $t !== '- ')->join("\n") ?: '- (henüz yok)';

        return <<<TXT
TripSpoiler (tripspoiler.com) için İngilizce blog yazıları yaz. TripSpoiler gezginlere şehirlerdeki en iyi turları, biletleri ve şehir kartlarını bulan bir gezi sitesi.

KAÇ YAZI: [ör. 15]
TÜR: [Rehber ya da Haber]
ŞEHİR: [Genel ya da şehir adı, ör. Istanbul]
KONULAR: [istersen konuları yaz; boş bırakırsan sen seç]

SİTEDE ZATEN OLAN YAZILAR (bunları ve bunlara çok benzeyenleri YAZMA):
{$existing}

KONU SEÇERKEN
- Turistin Google'a yazacağı net sorular seç (ör. "Is the Basilica Cistern worth it?", "Topkapi or Dolmabahce: which one to visit?", "Istanbul in 2 days"). Çok genel konulardan ("20 travel tips") kaçın.
- Her yazı farklı bir soruya cevap versin.

KURALLAR
- Yazılar İngilizce olsun, her biri 1200-1800 kelime, samimi ve işe yarar bir dille.
- Başlıklar için <h2> ve <h3>, paragraflar için <p>, listeler için <ul><li> kullan. Kalın için <strong>.
- Resim, tablo, stil, class, script KOYMA. Resimleri ben ekliyorum.
- Fiyat, saat, tarih gibi bilgileri uydurma; emin değilsen "check the official website before you go" de.
- Yazının içine link koyma.
- Her yazının sonunda 4-6 tane sık sorulan soru (FAQ) olsun.

UZUNLUK
- KAÇ YAZI dediysem hepsini tek kod bloğunda ver.
- Cevabın sığmazsa son yazıyı yarım bırakma: bitirdiğin yazıdan sonra dur, ben "devam" yazınca sıradakilerden devam et. Hepsi bitince "BİTTİ" yaz.

ÇIKTI: Sadece aşağıdaki kalıpta tek bir HTML kod bloğu ver, başka açıklama yazma. Her yazı ayrı bir <post> içinde:

<post>
<title>SEO dostu başlık (en fazla 70 karakter)</title>
<meta name="kind" content="guide">  (guide = şehir / müze / aktivite / gezi rehberi; news = güncel seyahat haberi: vize, ulaşım, etkinlik, turizm)
<meta name="excerpt" content="Liste sayfasında görünen 1-2 cümlelik özet (en fazla 300 karakter)">
<meta name="seo-title" content="Google başlığı (en fazla 60 karakter)">
<meta name="seo-description" content="Google açıklaması (140-160 karakter)">
<meta name="tags" content="etiket1, etiket2, etiket3, etiket4">
<article>
<p>Giriş paragrafı…</p>
<h2>…</h2>
<p>…</p>
</article>
<section class="faq">
<h3>Soru?</h3>
<p>Cevap.</p>
</section>
</post>
<post>
… (sıradaki yazı)
</post>
TXT;
    }

    /**
     * Kopyalanıp Claude'a / ChatGPT'ye verilecek talimat (şehir rehberi). Şehir sayfasından açılınca şehir yazılı gelir.
     */
    public static function cityGuidePrompt(?City $city = null): string
    {
        $topics = CityGuideTopic::orderBy('sort_order')->orderBy('id')->get()
            ->map(fn (CityGuideTopic $t) => '- data-topic="' . $t->key . '" → ' . ($t->getTranslation('name', 'en', false) ?: $t->key)
                . ($t->getTranslation('name', 'tr', false) ? ' (' . $t->getTranslation('name', 'tr', false) . ')' : '')
                . ($t->example ? ' · ' . preg_replace('/İstanbul|Istanbul/u', '[şehir]', $t->example) : ''))
            ->join("\n");
        $cities = City::orderBy('name')->pluck('name')->join(', ');
        $wanted = $city ? $city->name : '[ör. Istanbul, Antalya, Cappadocia]';

        return <<<TXT
TripSpoiler (tripspoiler.com) için şehir rehberlerini İngilizce yaz. TripSpoiler gezginlere şehirlerdeki en iyi turları, biletleri ve şehir kartlarını bulan bir gezi sitesi.

ŞEHİRLER: {$wanted}
(Şehir adını data-city'ye AYNEN bu listedeki gibi yaz. Paneldeki şehirler: {$cities})

HER ŞEHİR İÇİN ALANLAR (her biri için ayrı bir <section> yaz, data-topic değerini AYNEN kullan):
{$topics}

KURALLAR
- Her şey İngilizce olsun. Her alan 300-600 kelime, samimi ve işe yarar bir dille; şehre ilk kez gelen turiste yol göstersin.
- Her alanın başında bir <h2> başlık olsun (ör. "Best things to see in Antalya"). Altında <p>, <h3>, <ul><li>, <strong> kullan.
- Resim, tablo, stil, class, script KOYMA. Resimleri ben ekliyorum.
- Fiyat, saat gibi bilgileri uydurma; emin değilsen "check before you go" de. Link koyma.
- <header> içindeki başlık en fazla 100 karakter, açıklama 2-4 cümle (en fazla 1000 karakter) olsun.

UZUNLUK
- Bütün şehirleri tek kod bloğunda ver (her şehir ayrı bir <guide>).
- Cevabın sığmazsa şehri yarım bırakma: bitirdiğin şehirden sonra dur, ben "devam" yazınca sıradakinden devam et. Hepsi bitince "BİTTİ" yaz.

ÇIKTI: Sadece aşağıdaki kalıpta tek bir HTML kod bloğu ver, başka açıklama yazma:

<guide data-city="Şehir adı">
<header>
<h1>[Şehir] travel guide: …</h1>
<p>Şehri 2-4 cümleyle anlatan giriş.</p>
</header>
<section data-topic="sights">
<h2>…</h2>
<p>…</p>
</section>
… (her alan için bir section)
</guide>
TXT;
    }

    private static function blog(DOMXPath $xp, DOMNode $scope): array
    {
        $article = $xp->query('.//article', $scope)->item(0);
        $faqBox = $xp->query('.//*[@data-faq or contains(concat(" ", normalize-space(@class), " "), " faq ") or @id="faq"]', $scope)->item(0);
        $faqs = $faqBox ? self::faqs($faqBox) : [];
        $faqBox?->parentNode?->removeChild($faqBox);

        $title = self::text($xp->query('.//title', $scope)->item(0));
        // Başlık <title>'da yoksa yazının ilk <h1>'i; her durumda <h1> metinden çıkar (sayfa başlığı zaten var)
        $root = $article ?? $scope;
        foreach (iterator_to_array($xp->query('.//h1', $root)) as $h1) {
            $title = $title ?: self::text($h1);
            $h1->parentNode->removeChild($h1);
        }

        $tags = collect(explode(',', (string) self::meta($xp, $scope, 'tags')))
            ->map(fn ($t) => trim($t))->filter()->unique(fn ($t) => mb_strtolower($t))->values()->all();

        return [
            'title' => $title ? mb_substr($title, 0, 255) : null,
            'slug' => self::meta($xp, $scope, 'slug'),
            'excerpt' => self::limit(self::meta($xp, $scope, 'excerpt') ?? self::meta($xp, $scope, 'description'), 500),
            'meta_title' => self::limit(self::meta($xp, $scope, 'seo-title') ?? self::meta($xp, $scope, 'meta-title'), 255),
            'meta_description' => self::limit(self::meta($xp, $scope, 'seo-description') ?? self::meta($xp, $scope, 'meta-description'), 500),
            'tags' => $tags,
            'kind' => in_array($kind = strtolower(trim((string) self::meta($xp, $scope, 'kind'))), ['guide', 'news'], true) ? $kind : null,
            'content' => HtmlSanitizer::clean(self::inner($root, skip: ['title', 'meta', 'head', 'header', 'post'])),
            'faqs' => $faqs,
        ];
    }

    /**
     * @param  list<string>  $keys
     */
    private static function cityGuide(DOMXPath $xp, DOMNode $scope, array $keys): array
    {
        $header = $xp->query('.//header', $scope)->item(0);
        $title = self::text($xp->query('.//h1', $header ?? $scope)->item(0));
        $description = $header
            ? collect(iterator_to_array($xp->query('.//p', $header)))->map(fn ($p) => self::text($p))->filter()->join("\n\n")
            : null;

        $sections = [];
        $unknown = [];
        foreach ($xp->query('.//*[@data-topic]', $scope) as $box) {
            /** @var DOMElement $box */
            $key = trim(mb_strtolower($box->getAttribute('data-topic')));
            if (!in_array($key, $keys, true)) {
                $unknown[] = $key;
                continue;
            }
            $heading = $xp->query('.//h2', $box)->item(0) ?? $xp->query('.//h1', $box)->item(0);
            $sectionTitle = self::text($heading);
            $heading?->parentNode?->removeChild($heading);
            if (!$sectionTitle) {
                continue;
            }
            $sections[$key] = [
                'title' => mb_substr($sectionTitle, 0, 160),
                'content' => HtmlSanitizer::clean(self::inner($box)),
            ];
        }

        return [
            'title' => $title ? mb_substr($title, 0, 120) : null,
            'description' => self::limit($description ?: null, 1500),
            'sections' => $sections,
            'unknown' => array_values(array_unique($unknown)),
        ];
    }

    /**
     * @return array{0: DOMDocument, 1: DOMXPath}
     */
    private static function load(string $html): array
    {
        // ```html … ``` kod bloğu işaretleri kalırsa temizle (birden fazla blok da olabilir)
        $html = preg_replace('/^\s*```[a-z]*\s*$/im', '', $html) ?? $html;
        // <title> ve <meta> gövdede kalsın diye (yoksa ayrıştırıcı hepsini <head>'e taşır) yazı başına ayrı etikete çevrilir
        $html = preg_replace(['#<title\b#i', '#</title>#i', '#<meta\b#i'], ['<x-title', '</x-title>', '<x-meta'], $html) ?? $html;
        $doc = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><html><body>' . $html . '</body></html>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xp = new DOMXPath($doc);
        foreach (['x-title' => 'title', 'x-meta' => 'meta'] as $from => $to) {
            foreach (iterator_to_array($xp->query("//{$from}")) as $node) {
                $new = $doc->createElement($to);
                foreach ($node->attributes as $attr) {
                    $new->setAttribute($attr->name, $attr->value);
                }
                while ($node->firstChild) {
                    $new->appendChild($node->firstChild);
                }
                $node->parentNode->replaceChild($new, $node);
            }
        }

        return [$doc, $xp];
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    private static function faqs(DOMNode $box): array
    {
        $faqs = [];
        $current = null;
        $walk = function (DOMNode $node) use (&$walk, &$faqs, &$current) {
            foreach ($node->childNodes as $child) {
                if (!$child instanceof DOMElement) {
                    continue;
                }
                $tag = strtolower($child->tagName);
                if (in_array($tag, ['h2', 'h3', 'h4', 'summary', 'dt'], true)) {
                    $current !== null && $faqs[] = $current;
                    $current = ['question' => self::text($child), 'answer' => ''];
                } elseif (in_array($tag, ['details', 'div', 'dl'], true)) {
                    $walk($child);
                } elseif ($current !== null) {
                    $current['answer'] = trim($current['answer'] . "\n\n" . self::text($child));
                }
            }
        };
        $walk($box);
        $current !== null && $faqs[] = $current;

        return array_values(array_filter(
            array_map(fn ($f) => ['question' => mb_substr((string) $f['question'], 0, 500), 'answer' => mb_substr($f['answer'], 0, 5000)], $faqs),
            fn ($f) => $f['question'] !== '' && $f['answer'] !== '',
        ));
    }

    private static function meta(DOMXPath $xp, DOMNode $scope, string $name): ?string
    {
        $node = $xp->query('.//meta[translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="' . $name . '"]', $scope)->item(0);
        $value = $node instanceof DOMElement ? trim($node->getAttribute('content')) : '';

        return $value === '' ? null : $value;
    }

    private static function text(?DOMNode $node): ?string
    {
        $text = $node ? trim(preg_replace('/\s+/u', ' ', $node->textContent) ?? '') : '';

        return $text === '' ? null : $text;
    }

    private static function limit(?string $value, int $max): ?string
    {
        return $value === null ? null : mb_substr($value, 0, $max);
    }

    /**
     * @param  list<string>  $skip
     */
    private static function inner(DOMNode $node, array $skip = []): string
    {
        $html = '';
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && in_array(strtolower($child->tagName), $skip, true)) {
                continue;
            }
            $html .= $node->ownerDocument->saveHTML($child);
        }

        return $html;
    }
}
