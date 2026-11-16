<?php

namespace App\Support;

use App\Models\Blog;
use App\Models\CityGuideSection;
use Illuminate\Support\Collection;

/**
 * Resmi eksik içerik: kapak resmi olmayan blog yazıları ve yazılmış ama kapak resmi olmayan şehir rehberi alanları.
 * Menüdeki kırmızı sayılar ve listelerdeki "Resim eksik" etiketleri buradan gelir.
 */
class MissingImages
{
    public static function blogs(): int
    {
        return Blog::whereNull('cover_path')->count();
    }

    /**
     * Sayfa hero'sunda kendi resmi olmayan şehirler (Aktiviteler / Şehirler sayfası), sayfa anahtarına göre.
     *
     * @return array<string, int>  ör. ['cities' => 1]
     */
    public static function cityBanners(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $cache = [];
        foreach (array_keys(\App\Http\Controllers\PageHeroController::SPECS) as $key) {
            $rows = \App\Http\Controllers\PageHeroTextController::rowCities($key, idsOnly: true);
            if ($rows->isEmpty()) {
                continue;
            }
            $withImage = \App\Models\PageBanner::where('page_key', $key)->whereNotNull('city_id')->whereNotNull('image')->pluck('city_id')->all();
            if ($n = $rows->diff($withImage)->count()) {
                $cache[$key] = $n;
            }
        }

        return $cache;
    }

    /**
     * Yazılmış (başlık + metin dolu) ama kapak resmi olmayan alanlar.
     *
     * @return Collection<int, CityGuideSection>
     */
    public static function guideSections(): Collection
    {
        return CityGuideSection::whereNull('image_path')->get()->filter->isWritten()->values();
    }
}
