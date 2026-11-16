<?php

namespace App\Support;

use App\Services\ImageUploader;

/**
 * Zengin metin editöründen (Quill) gelen HTML'i sitede gösterilecek güvenli hale getirir:
 * yalnızca metin biçimlendirme etiketleri kalır, betikler ve olay öznitelikleri (onclick…) silinir.
 * Görseller sadece bizim media diskimizden ise kalır (base64 ya da başka sitedeki görsel silinir).
 */
class HtmlSanitizer
{
    private const ALLOWED_TAGS = '<p><br><h1><h2><h3><h4><strong><b><em><i><u><s><a><ul><ol><li><blockquote><span><img>';

    public static function clean(?string $html): ?string
    {
        if (blank($html) || (trim(strip_tags($html)) === '' && !str_contains($html, '<img'))) {
            return null;
        }

        $html = preg_replace('#<(script|style|iframe)\b[^>]*>.*?</\1>#is', '', $html) ?? '';
        $html = strip_tags($html, self::ALLOWED_TAGS);
        $html = preg_replace('#\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html) ?? '';
        $html = preg_replace('#\s(style|class)\s*=\s*("[^"]*"|\'[^\']*\')#i', '', $html) ?? '';
        $html = preg_replace('#href\s*=\s*(["\']?)\s*(javascript|data|vbscript):[^"\'>\s]*\1#i', 'href="#"', $html) ?? '';
        $html = preg_replace_callback('#<img\b[^>]*>#i', fn ($m) => self::image($m[0]), $html) ?? '';
        // Son kontrol (izin listesi): yukarıdaki kalıpları atlatan her şey (ör. <span/onclick=…>, java&#x73;cript:) burada düşer
        $html = self::scrub($html);
        // Editörün koyduğu bölünmez boşluklar (satır kırılmasın diye) normal boşluk olur
        $html = str_replace(['&nbsp;', "\u{00A0}"], ' ', $html);
        $html = self::markdownHeadings($html);

        return trim($html) === '' ? null : trim($html);
    }

    /**
     * Yapıştırılan metindeki "## Başlık" / "### Alt başlık" satırları gerçek başlık olur (h2 / h3),
     * "**kalın**" kalın yazı olur. Böylece hazır yazı yapıştırınca başlıkları tek tek seçmek gerekmez.
     */
    public static function markdownHeadings(string $html): string
    {
        $html = preg_replace_callback('#<p>\s*(?:<(?:strong|b)>)?\s*(\#{1,4})\s+(.*?)\s*(?:</(?:strong|b)>)?\s*</p>#is', function ($m) {
            $level = min(4, max(2, strlen($m[1]))); // "#" ve "##" → h2, "###" → h3, "####" → h4
            $text = trim(preg_replace('#</?(?:strong|b)>#i', '', $m[2]));

            return $text === '' ? $m[0] : "<h{$level}>{$text}</h{$level}>";
        }, $html) ?? $html;

        return preg_replace('#\*\*(\S(?:.*?\S)?)\*\*#s', '<strong>$1</strong>', $html) ?? $html;
    }

    /**
     * Bütün media adresleri (ör. yazı içi görselleri silerken hangilerinin kullanıldığını bulmak için).
     *
     * @return list<string>  media diskindeki yollar
     */
    public static function imagePaths(?string $html): array
    {
        preg_match_all('#<img\b[^>]*\bsrc="' . preg_quote(ImageUploader::baseUrl(), '#') . '([^"]+)"#i', (string) $html, $m);

        return array_values(array_unique($m[1]));
    }

    /** Etiket başına izin verilen öznitelikler; diğer hepsi silinir */
    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'target', 'rel'],
        'img' => ['src', 'alt', 'width', 'loading'],
    ];

    /**
     * HTML'i ayrıştırıp izin listesine göre yeniden kurar: izinli olmayan etiket açılır (metni kalır),
     * izinli olmayan öznitelik silinir, bağlantı yalnızca http(s), mailto, tel, # ya da site içi adres olabilir.
     */
    private static function scrub(string $html): string
    {
        if (trim($html) === '') {
            return $html;
        }

        $doc = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = (new \DOMXPath($doc))->query('//div[@id="__root"]')->item(0);
        if (!$root) {
            return '';
        }
        $allowedTags = array_map(fn ($t) => trim($t, '<>'), explode('><', self::ALLOWED_TAGS));
        self::scrubChildren($root, $allowedTags);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return $out;
    }

    /**
     * @param  list<string>  $allowedTags
     */
    private static function scrubChildren(\DOMNode $node, array $allowedTags): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMComment || $child instanceof \DOMProcessingInstruction) {
                $node->removeChild($child);
                continue;
            }
            if (!$child instanceof \DOMElement) {
                continue;
            }
            $tag = strtolower($child->tagName);
            if (!in_array($tag, $allowedTags, true)) {
                // izinsiz etiket: tehlikeliyse içeriğiyle, değilse sadece kendisi silinir
                if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'template', 'noscript', 'form', 'input', 'textarea', 'button', 'select'], true)) {
                    $node->removeChild($child);
                    continue;
                }
                self::scrubChildren($child, $allowedTags);
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }
            $allowed = self::ALLOWED_ATTRIBUTES[$tag] ?? [];
            foreach (iterator_to_array($child->attributes) as $attr) {
                $name = strtolower($attr->name);
                if (!in_array($name, $allowed, true)) {
                    $child->removeAttribute($attr->name);
                } elseif ($name === 'href' && !self::safeUrl($attr->value)) {
                    $child->setAttribute('href', '#');
                } elseif ($name === 'src' && (!str_starts_with($attr->value, ImageUploader::baseUrl()) || str_contains($attr->value, '..'))) {
                    $node->removeChild($child);
                    continue 2;
                } elseif ($name === 'target' && $attr->value !== '_blank') {
                    $child->removeAttribute('target');
                }
            }
            if ($tag === 'a' && $child->getAttribute('target') === '_blank') {
                $child->setAttribute('rel', 'noopener noreferrer');
            }
            self::scrubChildren($child, $allowedTags);
        }
    }

    /** Bağlantı adresi güvenli mi (çözülmüş hali; görünmez karakterler atlatılamasın) */
    private static function safeUrl(string $url): bool
    {
        $url = preg_replace('/[\x00-\x20\x7F]+/u', '', html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '';
        if ($url === '' || str_starts_with($url, '#') || str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return true;
        }

        return (bool) preg_match('#^(https?:|mailto:|tel:)#i', $url);
    }

    /**
     * Görsel etiketini yeniden kurar: sadece src (media diskinden), alt ve editörde seçilen boyut
     * (width="33%|50%") kalır, lazy yüklenir.
     */
    private static function image(string $tag): string
    {
        preg_match('#\bsrc\s*=\s*"([^"]*)"#i', $tag, $src);
        preg_match('#\balt\s*=\s*"([^"]*)"#i', $tag, $alt);
        preg_match('#\bwidth\s*=\s*"(33%|50%)"#i', $tag, $width);

        if (!isset($src[1]) || !str_starts_with($src[1], ImageUploader::baseUrl()) || str_contains($src[1], '..')) {
            return '';
        }

        return '<img src="' . e($src[1]) . '" alt="' . e(html_entity_decode($alt[1] ?? '')) . '"' . (isset($width[1]) ? ' width="' . $width[1] . '"' : '') . ' loading="lazy">';
    }
}
