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
        // Editörün koyduğu bölünmez boşluklar (satır kırılmasın diye) normal boşluk olur
        $html = str_replace(['&nbsp;', "\u{00A0}"], ' ', $html);

        return trim($html) === '' ? null : trim($html);
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
