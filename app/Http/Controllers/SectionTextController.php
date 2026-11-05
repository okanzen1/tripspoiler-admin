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
    public const IMAGES = ['city_entry' => 'city_entry_image'];

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

        $imageKey = self::IMAGES[$key] ?? null;

        return view('admin.section-texts.edit', [
            'key' => $key, 'meta' => self::SECTIONS[$key], 'model' => SectionText::for($key),
            'imageUrl' => $imageKey ? \App\Services\ImageUploader::url(\App\Models\Setting::get($imageKey)) : null,
            'hasImage' => (bool) $imageKey,
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
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
        ], [], ['title' => __('başlık'), 'image' => __('resim')]);

        // Resim (varsa): WebP'ye çevrilir, büyük + küçük kopya
        if ($request->hasFile('image') && $settingKey = self::IMAGES[$key] ?? null) {
            ini_set('memory_limit', '512M');
            \App\Models\Setting::put($settingKey, app(\App\Services\ImageUploader::class)->section($request->file('image'), \App\Models\Setting::get($settingKey)));
        }
        unset($data['image']);

        $record = SectionText::for($key);
        foreach ($data as $field => $value) {
            filled($value) ? $record->setTranslation($field, 'en', trim($value)) : $record->forgetTranslation($field, 'en');
        }
        $record->save();

        return back()->with('success', __(':name yazıları kaydedildi.', ['name' => __(self::SECTIONS[$key][0])]));
    }
}
