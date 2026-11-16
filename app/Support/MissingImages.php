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
     * Yazılmış (başlık + metin dolu) ama kapak resmi olmayan alanlar.
     *
     * @return Collection<int, CityGuideSection>
     */
    public static function guideSections(): Collection
    {
        return CityGuideSection::whereNull('image_path')->get()->filter->isWritten()->values();
    }
}
