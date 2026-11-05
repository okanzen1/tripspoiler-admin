<?php

namespace App\Services\Translation;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use RuntimeException;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Throwable;

/**
 * Ücretsiz Google Çeviri (anahtar / kota yok). Eski sistemdeki hatalar burada çözülür:
 * - HTML'e dokunulmaz: her paragraf/başlık/madde ayrı çevrilir, içindeki <strong>, <a href> gibi etiketler
 *   yer tutucuyla (#{0}) korunur; link adresleri ve görseller hiç çeviriye gitmez.
 * - Kısa metinler tek istekte toplu gönderilir, istekler arasında beklenir (Google engellemesin).
 */
class GoogleTranslator
{
    /** Bir istekte en fazla karakter (Google sınırı ~5000) */
    private const CHUNK = 4500;

    /** İstekler arası bekleme (mikrosaniye) */
    private const PAUSE = 300_000;

    /** Sitedeki dil kodu → Google dil kodu */
    private const TARGETS = ['zh' => 'zh-CN'];

    private const BLOCKS = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'blockquote', 'div', 'ul', 'ol', 'td', 'th', 'figcaption'];

    private ?GoogleTranslate $client = null;

    private bool $first = true;

    /**
     * Metinleri İngilizceden çevirir; sıra korunur, boş metin boş kalır.
     *
     * @param  list<string>  $texts
     * @return list<string>
     */
    public function translate(array $texts, string $locale, bool $html = false): array
    {
        return array_map(fn ($text) => trim((string) $text) === '' ? '' : ($html ? $this->html($text, $locale) : $this->batch([$this->clean($text)], $locale)[0]), $texts);
    }

    /**
     * HTML: yaprak bloklar (p, h2, li…) ayrı ayrı çevrilir, iskelet aynen kalır.
     */
    private function html(string $html, string $locale): string
    {
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $root = $doc->getElementById('root');

        $segments = [];
        $this->collect($root, $segments, root: true);
        if ($segments === []) {
            return $html;
        }

        $translated = $this->batch(array_map(fn ($segment) => $segment['text'], $segments), $locale);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        // İşaretlerin yerine çeviri (yer tutucular bozulduysa o parçanın aslı kalır)
        foreach ($segments as $i => $segment) {
            $out = str_replace(self::marker($i), $this->restore($translated[$i], $segment) ?? $this->restore($segment['text'], $segment), $out);
        }

        return $out;
    }

    private static function marker(int $i): string
    {
        return "TSSEG{$i}TSSEG";
    }

    /**
     * Çevrilecek parçaları toplar: içinde başka blok olmayan bloklar (içi boşaltılır) ve blok dışındaki çıplak metinler.
     */
    private function collect(DOMNode $node, array &$segments, bool $root = false): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement && in_array(strtolower($child->tagName), self::BLOCKS, true)) {
                if ($this->hasBlocks($child)) {
                    $this->collect($child, $segments);
                } elseif (trim($child->textContent) !== '') {
                    $inner = '';
                    foreach ($child->childNodes as $grandchild) {
                        $inner .= $child->ownerDocument->saveHTML($grandchild);
                    }
                    $child->textContent = self::marker(count($segments));
                    $segments[] = $this->placeholders($inner);
                }
            } elseif ($root && $child instanceof DOMText && trim($child->textContent) !== '') {
                $text = $child->textContent;
                $child->textContent = self::marker(count($segments));
                $segments[] = $this->placeholders(htmlspecialchars($text, ENT_NOQUOTES));
            }
        }
    }

    private function hasBlocks(DOMElement $element): bool
    {
        foreach ($element->getElementsByTagName('*') as $descendant) {
            if (in_array(strtolower($descendant->tagName), self::BLOCKS, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Satır içi etiketleri #{n} yer tutucusuna çevirir: "Great <strong>tour</strong>" → "Great #{0}tour#{1}"
     *
     * @return array{text: string, tags: list<string>}
     */
    private function placeholders(string $inner): array
    {
        $tags = [];
        $text = '';
        foreach (preg_split('/(<[^>]+>)/', $inner, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $part) {
            if (str_starts_with($part, '<')) {
                $text .= '#{' . count($tags) . '}';
                $tags[] = $part;
            } else {
                $text .= html_entity_decode($part, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        return ['text' => $this->clean($text), 'tags' => $tags];
    }

    /**
     * Çeviriyi HTML'e geri çevirir; yer tutucular eksik/bozuksa null (çağıran yedek kullanır).
     */
    private function restore(string $translated, array $segment): ?string
    {
        $translated = preg_replace('/#\s*\{\s*(\d+)\s*\}/u', '#{$1}', $translated);
        preg_match_all('/#\{(\d+)\}/', $translated, $found);
        $expected = array_keys($segment['tags']);
        $foundIds = array_map('intval', $found[1]);
        sort($foundIds);
        if ($foundIds !== $expected) {
            return null;
        }

        $html = '';
        foreach (preg_split('/(#\{\d+\})/', $translated, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $part) {
            $html .= preg_match('/^#\{(\d+)\}$/', $part, $m) ? $segment['tags'][(int) $m[1]] : htmlspecialchars($part, ENT_NOQUOTES);
        }

        return $html;
    }

    /**
     * Metinleri satır satır birleştirip az istekle çevirir; satır sayısı tutmazsa tek tek çevirir.
     *
     * @param  list<string>  $texts  (satır sonu içermez)
     * @return list<string>
     */
    private function batch(array $texts, string $locale): array
    {
        $result = [];
        $chunk = [];
        $size = 0;

        $flush = function () use (&$chunk, &$size, &$result, $locale) {
            if ($chunk === []) {
                return;
            }
            $lines = explode("\n", (string) $this->request(implode("\n", $chunk), $locale));
            if (count($lines) !== count($chunk)) {
                $lines = array_map(fn ($text) => (string) $this->request($text, $locale), $chunk);
            }
            array_push($result, ...array_map('trim', $lines));
            $chunk = [];
            $size = 0;
        };

        foreach ($texts as $text) {
            if ($chunk !== [] && $size + mb_strlen($text) + 1 > self::CHUNK) {
                $flush();
            }
            $chunk[] = $text;
            $size += mb_strlen($text) + 1;
        }
        $flush();

        return $result;
    }

    protected function request(string $text, string $locale): string
    {
        if (mb_strlen($text) > self::CHUNK) {
            // Tek parçası çok uzun metin: cümle sonlarından bölünür
            return implode(' ', array_map(fn ($part) => $this->request($part, $locale), $this->split($text)));
        }

        if (!$this->first) {
            usleep(self::PAUSE);
        }
        $this->first = false;

        $client = $this->client ??= (new GoogleTranslate())->setSource('en')->setOptions(['timeout' => 30]);
        $client->setTarget(self::TARGETS[$locale] ?? $locale);

        foreach ([0, 2, 5] as $wait) {
            sleep($wait);
            try {
                return (string) $client->translate($text);
            } catch (Throwable $e) {
                $last = $e;
            }
        }

        throw new RuntimeException(__('Google Çeviri şu an yanıt vermiyor (çok sık istek olabilir); birkaç dakika sonra tekrar dene.'), previous: $last);
    }

    /**
     * @return list<string>
     */
    private function split(string $text): array
    {
        $parts = [];
        $current = '';
        foreach (preg_split('/(?<=[.!?])\s+/u', $text) as $sentence) {
            if ($current !== '' && mb_strlen($current) + mb_strlen($sentence) + 1 > self::CHUNK) {
                $parts[] = $current;
                $current = '';
            }
            $current = trim($current . ' ' . $sentence);
        }

        return [...$parts, $current];
    }

    private function clean(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text));
    }
}
