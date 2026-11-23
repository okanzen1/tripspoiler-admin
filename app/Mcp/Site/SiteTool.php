<?php

namespace App\Mcp\Site;

use App\Mcp\Tools\PanelTool;
use App\Models\Language;

/**
 * Herkese açık site bağlantısının (SiteServer) araçlarının tabanı: sadece okur, sadece sitede yayında olanı gösterir,
 * bağlantılar sitenin (tripspoiler.com) adresleridir. Panelle ilgili hiçbir bilgi (düzenleme linki, taslak) vermez.
 */
abstract class SiteTool extends PanelTool
{
    protected function locale(?string $locale): string
    {
        $locale = strtolower(trim((string) $locale));

        return $locale !== '' && Language::targets()->pluck('code')->push(Language::SOURCE)->contains($locale) ? $locale : Language::SOURCE;
    }

    /** Sitedeki sayfa adresi (config/site.php paths) */
    protected function siteUrl(string $type, string $slug, string $locale): string
    {
        return config('site.url') . strtr(config("site.paths.{$type}"), ['{locale}' => $locale, '{slug}' => $slug]);
    }

    /** Kaydın o dildeki adresi (aktivite / blog: dile göre slug, yoksa İngilizce slug) */
    protected function slug(\Illuminate\Database\Eloquent\Model $model, string $locale): string
    {
        $slugs = (array) ($model->slugs ?? []);

        return is_string($slugs[$locale] ?? null) && $slugs[$locale] !== '' ? $slugs[$locale] : (string) $model->slug;
    }

    /** HTML'den düz, kısa metin */
    protected function plain(?string $html, int $limit = 600): ?string
    {
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_replace(['</p>', '</li>', '<br>'], [' ', '; ', ' '], (string) $html)))));

        return $text === '' ? null : \Illuminate\Support\Str::limit($text, $limit);
    }

    /** Arama sözcükleri (LIKE için kaçışlı) */
    protected function like(string $search): string
    {
        return '%' . addcslashes(trim($search), '%_\\') . '%';
    }
}
