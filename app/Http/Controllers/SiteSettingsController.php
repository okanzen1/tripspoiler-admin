<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Blog;
use App\Models\HeroText;
use App\Models\Setting;
use App\Services\ImageUploader;
use App\Support\HeroSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Site ayarları: giriş sayfası (kartlar) ve "Hero alanı" — anasayfanın en üst kısmı: arka plan resmi ve oradaki kartlar.
 */
class SiteSettingsController extends Controller
{
    /** Yüklenebilecek en büyük görsel (KB); sunucunun upload_max_filesize ayarı da en az bu kadar olmalı */
    public const MAX_KB = 15360;

    public function __construct(private ImageUploader $uploader) {}

    public function index()
    {
        return view('admin.site-settings.index', [
            'hero' => HeroSettings::all(),
            'announcementCount' => \App\Models\Announcement::active()->count(),
            'features' => \App\Models\Feature::active()->orderBy('sort_order')->limit(3)->get(),
            'featureCount' => \App\Models\Feature::active()->count(),
            'homeBlogs' => \App\Models\Blog::whereIn('id', array_filter(explode(',', (string) \App\Models\Setting::get('home_blog_ids'))))->get(),
            'steps' => \App\Models\Step::active()->orderBy('sort_order')->orderBy('id')->limit(4)->get(),
        ]);
    }

    /**
     * Bölümü aç/kapa: kapalı bölüm sitenin hiçbir sayfasında görünmez.
     */
    public function toggleSection(Request $request, string $section)
    {
        abort_unless(in_array($section, \App\Support\SiteSections::KEYS, true), 404);
        $active = \App\Support\SiteSections::toggle($section);
        $message = __($active ? 'Bölüm yayına alındı; sitede görünüyor.' : 'Bölüm gizlendi; sitenin hiçbir yerinde görünmez.');

        return $request->expectsJson() ? response()->json(['active' => $active, 'message' => $message]) : back()->with('success', $message);
    }

    public function hero()
    {
        return view('admin.site-settings.hero', [
            'hero' => HeroSettings::all(),
            'heroText' => HeroText::current(),
            'maxMb' => self::MAX_KB / 1024,
            'serverMaxMb' => round(\App\Support\UploadLimit::fileBytes() / 1024 ** 2, 1),
            'activities' => Activity::with(['city', 'cover'])->orderByDesc('active')->orderBy('city_id')->orderBy('sort_order')->orderBy('id')->get(),
            'blogs' => Blog::with('city')->orderByDesc('active')->orderByDesc('published_at')->orderByDesc('id')->get(),
        ]);
    }

    public function updateHero(Request $request)
    {
        $data = $request->validate([
            'hero_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:' . self::MAX_KB, 'dimensions:min_width=1600,min_height=800'],
            'hero_image_mobile' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:' . self::MAX_KB, 'dimensions:min_width=720,min_height=1100'],
            'remove_hero_image_mobile' => ['boolean'],
            'hero_focus' => ['required', 'integer', 'between:0,100'],
            'hero_overlay' => ['required', Rule::in(HeroSettings::OVERLAYS)],
            'hero_featured' => ['nullable', 'array', 'max:' . HeroSettings::FEATURED_MAX],
            'hero_featured.*' => ['nullable', 'integer', Rule::exists('activities', 'id')],
            'hero_pass_id' => ['nullable', 'integer', Rule::exists('activities', 'id')->where('type', 'pass')],
            'hero_guide_id' => ['nullable', 'integer', Rule::exists('blogs', 'id')],
            'text' => ['array'],
            'text.eyebrow' => ['nullable', 'string', 'max:60'],
            'text.title_1' => ['required', 'string', 'max:40'],
            'text.title_2' => ['nullable', 'string', 'max:40'],
            'text.title_3' => ['nullable', 'string', 'max:40'],
            'text.title_highlight' => ['nullable', 'string', 'max:40'],
            'text.description' => ['nullable', 'string', 'max:260'],
        ], [
            'hero_image.dimensions' => __('Masaüstü görseli en az 1600 × 800 piksel olmalı (önerilen 2560 × 1440).'),
            'hero_image_mobile.dimensions' => __('Telefon görseli en az 720 × 1100 piksel olmalı (önerilen 1080 × 1920).'),
            'hero_image.max' => __('Görsel en fazla :max MB olabilir.', ['max' => self::MAX_KB / 1024]),
            'hero_image_mobile.max' => __('Görsel en fazla :max MB olabilir.', ['max' => self::MAX_KB / 1024]),
        ], [
            'hero_image' => __('masaüstü görseli'),
            'hero_image_mobile' => __('telefon görseli'),
            'hero_featured' => __('popüler kartı'),
            'hero_featured.*' => __('popüler aktivite'),
            'hero_pass_id' => __('şehir kartı'),
            'hero_guide_id' => __('blog yazısı'),
            'text.title_1' => __('başlık 1. satır'),
        ]);

        // Büyük fotoğrafları çözmek için geçici olarak daha fazla bellek
        ini_set('memory_limit', '512M');

        if ($request->hasFile('hero_image')) {
            try {
                Setting::put('hero_image', $this->uploader->hero($request->file('hero_image'), Setting::get('hero_image')));
            } catch (\Throwable $e) {
                report($e);
                return back()->withInput()->with('error', __('Resim yüklenemedi: :msg', ['msg' => $e->getMessage()]));
            }
        }

        if ($request->hasFile('hero_image_mobile')) {
            Setting::put('hero_image_mobile', $this->uploader->heroMobile($request->file('hero_image_mobile'), Setting::get('hero_image_mobile')));
        } elseif ($request->boolean('remove_hero_image_mobile')) {
            $this->uploader->delete(Setting::get('hero_image_mobile'));
            Setting::put('hero_image_mobile', null);
        }

        Setting::put('hero_focus', (string) $data['hero_focus']);
        Setting::put('hero_overlay', $data['hero_overlay']);

        // Kart seçimleri: boş bırakılanlar sitede otomatik dolar
        $featured = array_values(array_unique(array_filter(array_map('intval', $data['hero_featured'] ?? []))));
        Setting::put('hero_featured', $featured ? implode(',', $featured) : null);
        Setting::put('hero_pass_id', isset($data['hero_pass_id']) ? (string) $data['hero_pass_id'] : null);
        Setting::put('hero_guide_id', isset($data['hero_guide_id']) ? (string) $data['hero_guide_id'] : null);

        // Yazılar: sadece İngilizce yazılır; diğer diller Çeviriler'den (değişen dil "İngilizce değişti" olur)
        if ($request->has('text')) {
            $text = HeroText::current();
            foreach ($text->translatable as $field) {
                $value = trim((string) ($data['text'][$field] ?? ''));
                filled($value) ? $text->setTranslation($field, 'en', $value) : $text->forgetTranslation($field, 'en');
            }
            $text->save();
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => __('Hero alanı kaydedildi. Anasayfada hemen görünür.')]);
        }

        return back()->with('success', __('Hero alanı kaydedildi. Anasayfada hemen görünür.'));
    }
}
