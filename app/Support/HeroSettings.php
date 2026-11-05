<?php

namespace App\Support;

use App\Models\Setting;
use App\Services\ImageUploader;

/**
 * Anasayfa hero ayarları (settings tablosu). Site de aynı anahtarları okur.
 *
 * hero_image         masaüstü görsel yolu (media diski; "-md" 1920 ve "-sm" 1280 kopyalarıyla)
 * hero_image_mobile  telefon görseli (isteğe bağlı; boşsa masaüstü görseli kırpılır)
 * hero_focus         görsel kırpılırken yatay odak noktası, 0 (sol) – 100 (sağ)
 * hero_overlay       yazının okunması için koyulaştırma: soft | medium | strong
 * hero_featured      "Şu an popüler" kartında dönen aktiviteler, sırayla ("3,7,1"); boşsa popüler işaretliler
 * hero_pass_id       sol beyaz kart: şehir kartı (pass); boşsa sıralamadaki ilk şehir kartı
 * hero_guide_id      sağ beyaz kart: blog yazısı; boşsa en yeni yazı
 */
class HeroSettings
{
    public const OVERLAYS = ['soft', 'medium', 'strong'];

    /** "Şu an popüler" kartında en fazla kaç aktivite döner */
    public const FEATURED_MAX = 5;

    public static function all(): array
    {
        $image = Setting::get('hero_image');
        $mobile = Setting::get('hero_image_mobile');

        return [
            'image' => $image,
            'image_url' => ImageUploader::url($image),
            'image_mobile' => $mobile,
            'image_mobile_url' => ImageUploader::url($mobile),
            'focus' => (int) (Setting::get('hero_focus') ?? 50),
            'overlay' => in_array(Setting::get('hero_overlay'), self::OVERLAYS, true) ? Setting::get('hero_overlay') : 'medium',
            'featured' => array_values(array_filter(array_map('intval', explode(',', (string) Setting::get('hero_featured'))))),
            'pass_id' => (int) Setting::get('hero_pass_id') ?: null,
            'guide_id' => (int) Setting::get('hero_guide_id') ?: null,
        ];
    }
}
