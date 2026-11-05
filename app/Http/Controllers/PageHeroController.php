<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Page;
use App\Models\PageBanner;
use App\Services\ImageUploader;
use Illuminate\Http\Request;

/**
 * Sayfa banner resimleri (Aktiviteler, Şehirler): genel resim + isteğe bağlı her şehre ayrı resim;
 * her biri için ayrıca telefon resmi. Sıra: şehrin resmi → sayfanın genel resmi → anasayfa hero'su.
 */
class PageHeroController extends Controller
{
    /**
     * Sayfaya göre ölçüler ve anlatım. desktop/mobile: [önerilen, en az] (genişlik × yükseklik).
     */
    public const SPECS = [
        'activities' => [
            'desktop' => [[2400, 1000], [1600, 600]],
            'mobile' => [[1080, 1350], [720, 900]],
            'ratio' => 'aspect-[12/5]',
            'preview_width' => 'max-w-2xl',
            'mobile_ratio' => 'aspect-[4/5]',
            'layout' => 'Fotoğraf sayfanın üstünü tamamen kaplar; sol tarafa yazılar gelir ve resim biraz koyulaştırılır. Şehri tanıtan, ortası ve sağı dolu bir manzara en iyi sonucu verir.',
        ],
        'reviews' => [
            'desktop' => [[2400, 1000], [1600, 600]],
            'mobile' => [[1080, 1350], [720, 900]],
            'ratio' => 'aspect-[12/5]',
            'preview_width' => 'max-w-2xl',
            'mobile_ratio' => 'aspect-[4/5]',
            'layout' => 'Fotoğraf sayfanın üstünü tamamen kaplar; solda yazılar, sağda puan kartı durur ve resim koyulaştırılır. Mutlu gezginler ya da bir şehir manzarası iyi gider.',
        ],
        'about' => [
            'desktop' => [[2400, 1200], [1600, 800]],
            'mobile' => [[1080, 1350], [720, 900]],
            'ratio' => 'aspect-[2/1]',
            'preview_width' => 'max-w-2xl',
            'mobile_ratio' => 'aspect-[4/5]',
            'layout' => 'Fotoğraf köşeleri yuvarlak büyük bir kart olarak sayfanın üstünü kaplar; yazılar sol alta gelir ve alt kısım koyulaştırılır. Ekibi, bir şehri ya da yolculuğu anlatan sıcak bir fotoğraf seç.',
        ],
        'cities' => [
            'desktop' => [[2000, 1600], [1200, 960]],
            'mobile' => [[1200, 900], [800, 600]],
            'ratio' => 'aspect-[5/4]',
            'preview_width' => 'max-w-sm',
            'mobile_ratio' => 'aspect-[4/3]',
            'layout' => 'Fotoğraf sağda, köşeleri yuvarlak büyük bir kart olarak görünür; üstüne rehberden küçük kartlar biner. Şehri tek karede anlatan, net bir manzara seç.',
        ],
    ];

    public function __construct(private ImageUploader $uploader) {}

    /** Eski adres: banner resimleri artık Hero alanı ekranında */
    public function edit(Page $page)
    {
        return redirect()->route('pages.hero-text', $page);
    }

    /**
     * Resimler (Hero alanı formundan çağrılır): genel + şehre özel, masaüstü ve telefon. Doğrulama hatası formda görünür.
     */
    public function saveImages(Request $request, Page $page): void
    {
        $spec = self::SPECS[$page->key];

        $desktop = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:' . SiteSettingsController::MAX_KB, 'dimensions:min_width=' . $spec['desktop'][1][0] . ',min_height=' . $spec['desktop'][1][1]];
        $mobile = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:' . SiteSettingsController::MAX_KB, 'dimensions:min_width=' . $spec['mobile'][1][0] . ',min_height=' . $spec['mobile'][1][1]];
        $desktopMessage = __('Masaüstü resmi en az :w × :h piksel olmalı.', ['w' => $spec['desktop'][1][0], 'h' => $spec['desktop'][1][1]]);
        $mobileMessage = __('Telefon resmi en az :w × :h piksel olmalı.', ['w' => $spec['mobile'][1][0], 'h' => $spec['mobile'][1][1]]);

        $request->validate([
            'general' => $desktop,
            'general_mobile' => $mobile,
            'city' => ['array'],
            'city.*' => $desktop,
            'city_mobile' => ['array'],
            'city_mobile.*' => $mobile,
            'remove' => ['array'],
        ], [
            'general.dimensions' => $desktopMessage,
            'city.*.dimensions' => $desktopMessage,
            'general_mobile.dimensions' => $mobileMessage,
            'city_mobile.*.dimensions' => $mobileMessage,
            'max' => __('Görsel en fazla :max MB olabilir.', ['max' => SiteSettingsController::MAX_KB / 1024]),
        ]);

        ini_set('memory_limit', '512M');

        $this->save($page->key, null, $request->file('general'), $request->file('general_mobile'),
            $request->boolean('remove.general'), $request->boolean('remove.general_mobile'));

        $ids = array_unique([
            ...array_keys($request->file('city', [])),
            ...array_keys($request->file('city_mobile', [])),
            ...array_keys((array) $request->input('remove.city', [])),
            ...array_keys((array) $request->input('remove.city_mobile', [])),
        ]);
        foreach (City::whereIn('id', $ids)->pluck('id') as $id) {
            $this->save($page->key, $id, $request->file("city.{$id}"), $request->file("city_mobile.{$id}"),
                $request->boolean("remove.city.{$id}"), $request->boolean("remove.city_mobile.{$id}"));
        }
    }

    /**
     * Tek resim alanı (genel ya da bir şehir): yeni dosya yüklenir ya da kaldırılır; ikisi de boşalırsa kayıt silinir.
     */
    private function save(string $pageKey, ?int $cityId, $desktop, $mobile, bool $removeDesktop, bool $removeMobile): void
    {
        if (!$desktop && !$mobile && !$removeDesktop && !$removeMobile) {
            return;
        }

        $banner = PageBanner::firstOrNew(['page_key' => $pageKey, 'city_id' => $cityId]);

        if ($desktop) {
            $banner->image = $this->uploader->hero($desktop, $banner->image);
        } elseif ($removeDesktop && $banner->image) {
            $this->uploader->delete($banner->image);
            $banner->image = null;
        }

        if ($mobile) {
            $banner->image_mobile = $this->uploader->heroMobile($mobile, $banner->image_mobile);
        } elseif ($removeMobile && $banner->image_mobile) {
            $this->uploader->delete($banner->image_mobile);
            $banner->image_mobile = null;
        }

        if ($banner->image || $banner->image_mobile) {
            $banner->save();
        } elseif ($banner->exists) {
            $banner->delete();
        }
    }
}
