<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\CityGuide;
use App\Models\CityGuideSection;
use App\Models\CityGuideTopic;
use App\Services\ImageUploader;
use App\Support\HtmlSanitizer;
use App\Support\PageSections;
use Illuminate\Http\Request;

/**
 * Şehir rehberi: her şehrin genel başlığı/açıklaması ve alanları (Gezilecek yerler, Yeme içme…).
 * Her şehir alanları kendi sırasına koyar, açar/kapar ve kendi metnini yazar.
 */
class CityGuideController extends Controller
{
    public function __construct(private ImageUploader $uploader) {}

    public function index()
    {
        $topicCount = CityGuideTopic::count();

        // Yayındaki şehirler ve aktivitesi olanlar
        $cities = City::with('country')
            ->withCount(['activities', 'guideSections as live_count' => fn ($q) => $q->where('active', true)])
            ->where(fn ($q) => $q->where('active', true)->orHas('activities'))
            ->orderByDesc('activities_count')->orderBy('name')
            ->get();

        return view('admin.city-guides.index', compact('cities', 'topicCount'));
    }

    public function show(City $city)
    {
        $this->ensureSections($city);
        $order = PageSections::guideOrder();

        return view('admin.city-guides.show', [
            'city' => $city->load('country'),
            'guide' => CityGuide::firstOrNew(['city_id' => $city->id]),
            // Sıra: Sayfalar › Şehirler › Bölümler
            'sections' => $city->guideSections()->with('topic')->get()
                ->sortBy(fn (CityGuideSection $section) => ($i = array_search($section->topic_id, $order, true)) === false ? 999 : $i)->values(),
        ]);
    }

    /**
     * Şehir sayfasının üstündeki genel başlık ve açıklama (İngilizce; diğer diller Çeviriler'den).
     */
    public function updateIntro(Request $request, City $city)
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1500'],
            'cards' => ['array', 'max:2'],
            'cards.*' => ['nullable', 'string', 'max:10'],
        ]);

        $guide = CityGuide::firstOrNew(['city_id' => $city->id]);
        foreach (['title', 'description'] as $field) {
            filled($data[$field] ?? null) ? $guide->setTranslation($field, 'en', trim($data[$field])) : $guide->forgetTranslation($field, 'en');
        }
        // Banner kartları: "auto" → otomatik, "0" → gösterme, sayı → o şehrin bölümü
        $cards = array_map(fn ($v) => $v === '0' ? '0' : ($city->guideSections()->whereKey((int) $v)->exists() ? (string) (int) $v : ''), array_pad(array_values($data['cards'] ?? []), 2, ''));
        $guide->hero_cards = array_filter($cards, 'strlen') ? implode(',', $cards) : null;
        $guide->save();

        return back()->with('success', __('Genel giriş kaydedildi.'));
    }

    /**
     * Bu şehirde hangi alanlar açık (her değişiklikte tüm durum gelir: on = açık id'ler). Sıra Bölümler'de.
     */
    public function order(Request $request, City $city)
    {
        $data = $request->validate(['order' => ['array'], 'order.*' => ['integer'], 'on' => ['array'], 'on.*' => ['integer']]);
        $on = array_map('intval', $data['on'] ?? []);

        foreach ($city->guideSections()->get() as $section) {
            $section->update(['active' => in_array($section->id, $on, true)]);
        }

        return response()->json(['message' => __('Kaydedildi. Sitede hemen görünür.')]);
    }

    public function editSection(City $city, CityGuideSection $section)
    {
        abort_unless($section->city_id === $city->id, 404);

        return view('admin.city-guides.section', [
            'city' => $city,
            'section' => $section->load('topic'),
            'maxMb' => SiteSettingsController::MAX_KB / 1024,
        ]);
    }

    public function updateSection(Request $request, City $city, CityGuideSection $section)
    {
        abort_unless($section->city_id === $city->id, 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'content' => ['nullable', 'string', 'max:300000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:' . SiteSettingsController::MAX_KB],
            'remove_image' => ['boolean'],
            'active' => ['boolean'],
        ], [], ['title' => __('başlık'), 'image' => __('kapak resmi')]);

        $section->setTranslation('title', 'en', trim($data['title']));
        $content = HtmlSanitizer::clean($data['content'] ?? null);
        $content ? $section->setTranslation('content', 'en', $content) : $section->forgetTranslation('content', 'en');

        if ($request->hasFile('image')) {
            ini_set('memory_limit', '512M');
            $section->image_path = $this->uploader->section($request->file('image'), $section->image_path);
        } elseif ($request->boolean('remove_image') && $section->image_path) {
            $this->uploader->delete($section->image_path);
            $section->image_path = null;
        }

        $section->active = $request->boolean('active');
        $section->save();

        // Aynı sayfada kal: ilk kayıttan sonra çeviri kutusu hemen görünsün
        return redirect()->route('city-guides.sections.edit', [$city, $section])->with('success', __(':topic kaydedildi.', ['topic' => $section->topic->getTranslation('name', 'tr', false) ?: $section->topic->name]));
    }

    /**
     * Şehirde henüz satırı olmayan alanlar için boş (kapalı) kayıt açar; yeni eklenen alanlar sona gelir.
     */
    private function ensureSections(City $city): void
    {
        $existing = $city->guideSections()->pluck('topic_id')->all();
        $next = (int) $city->guideSections()->max('sort_order');

        CityGuideTopic::whereNotIn('id', $existing)->orderBy('sort_order')->orderBy('id')->get()
            ->each(function (CityGuideTopic $topic) use ($city, &$next) {
                CityGuideSection::create(['city_id' => $city->id, 'topic_id' => $topic->id, 'active' => false, 'sort_order' => ++$next]);
            });
    }
}
