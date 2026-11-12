<?php

namespace App\Http\Controllers;

use App\Models\SectionText;
use Illuminate\Http\Request;

/**
 * Anasayfa bölümlerinin başlık yazıları tek sayfada: "TripSpoiler nedir", "Sosyal medya", "Yorumlar".
 * (Nasıl çalışır ve Blog bölümünün yazıları kendi sayfalarında.)
 */
class SectionTextController extends Controller
{
    /** section_texts anahtarı → panelde görünen ad ve açıklama */
    /** Resmi olan bölümler → Setting anahtarı */
    /**
     * Bölümün resimleri: bölüm => [form alanı => [Setting anahtarı, panelde ad, açıklama, sitedeki varsayılan resim]]
     * Site aynı Setting anahtarlarını okur (city-entry-section, tripspoiler-intro); boşsa varsayılan resim.
     */
    public const IMAGES = [
        'city_entry' => [
            'image' => ['city_entry_image', 'Resim', 'Kartın arka planındaki fotoğraf. Yatay olsun (en iyisi 2000 × 1200); büyükse otomatik küçültülür.', 'images/tripspoiler-statement.webp'],
        ],
        'intro' => [
            'image_cities' => ['intro_cities_image', 'Şehirler kartının resmi', 'Sağdaki “Şehirler” kartının arkasındaki fotoğraf. Yatay olsun (en iyisi 1600 × 1000).', 'images/tripspoiler-statement.webp'],
            'image_experiences' => ['intro_experiences_image', 'Deneyimler kartının resmi', 'Sağdaki “Deneyimler” kartının arkasındaki fotoğraf. Yatay olsun (en iyisi 1600 × 1000).', 'images/all-activities-cover.webp'],
        ],
    ];

    /** Bölümde kullanılan alanlar (verilmeyenlerde dördü de) */
    public const FIELDS = ['partners' => ['eyebrow', 'title', 'title_highlight']];

    public const SECTIONS = [
        'intro' => ['TripSpoiler nedir', 'Solda tanıtım yazısı, sağda dört kısayol kartı olan bölüm.'],
        'social' => ['Sosyal medya', 'Instagram ve TikTok kartlarının yanındaki yazı.'],
        'reviews' => ['Yorumlar', 'Gezgin yorumlarının solundaki başlık ve açıklama.'],
        'partners' => ['Partnerler şeridi', 'Footer’ın üstünde kayan partner logoları. Logoları Yönetim > Partnerler’den seçersin.'],
        'city_entry' => ['Şehirler çağrısı', 'Büyük resimli kart: üstünde başlık, kısa yazı ve “Şehirleri keşfet” butonu.'],
    ];

    /** Eski adres: bölümler artık Site ayarları'nda ayrı kartlar */
    public function index()
    {
        return redirect()->route('site-settings.index');
    }

    public function edit(string $key)
    {
        abort_unless(isset(self::SECTIONS[$key]), 404);

        // Resim alanları: [form alanı, ad, açıklama, yüklenen resim adresi (yoksa null), varsayılan resim adresi]
        $images = collect(self::IMAGES[$key] ?? [])->map(fn ($image, $field) => [
            'field' => $field, 'label' => $image[1], 'hint' => $image[2],
            'url' => \App\Services\ImageUploader::url(\App\Models\Setting::get($image[0])),
            'default' => config('site.url') . '/' . $image[3],
        ])->values();

        return view('admin.section-texts.edit', [
            'key' => $key, 'meta' => self::SECTIONS[$key], 'model' => SectionText::for($key),
            'images' => $images,
            'fields' => self::FIELDS[$key] ?? ['eyebrow', 'title', 'title_highlight', 'description'],
        ]);
    }

    public function update(Request $request, string $key)
    {
        abort_unless(isset(self::SECTIONS[$key]), 404);

        $data = $request->validate([
            'eyebrow' => ['nullable', 'string', 'max:40'],
            'title' => ['required', 'string', 'max:60'],
            'title_highlight' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:260'],
            ...collect(self::IMAGES[$key] ?? [])->mapWithKeys(fn ($image, $field) => [$field => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:15360']])->all(),
        ], [], ['title' => __('başlık'), ...collect(self::IMAGES[$key] ?? [])->map(fn ($image) => mb_strtolower(__($image[1])))->all()]);

        // Resimler (seçildiyse): WebP'ye çevrilir, büyük + küçük kopya; eskisi silinir
        foreach (self::IMAGES[$key] ?? [] as $field => [$settingKey]) {
            if ($request->hasFile($field)) {
                ini_set('memory_limit', '512M');
                \App\Models\Setting::put($settingKey, app(\App\Services\ImageUploader::class)->section($request->file($field), \App\Models\Setting::get($settingKey)));
            }
            unset($data[$field]);
        }

        $record = SectionText::for($key);
        foreach ($data as $field => $value) {
            filled($value) ? $record->setTranslation($field, 'en', trim($value)) : $record->forgetTranslation($field, 'en');
        }
        $record->save();

        return back()->with('success', __(':name yazıları kaydedildi.', ['name' => __(self::SECTIONS[$key][0])]));
    }
}
