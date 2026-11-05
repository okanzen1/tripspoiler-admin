<?php

namespace App\Http\Controllers;

use App\Models\CityGuideTopic;
use App\Support\FeatureIcons;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Şehir rehberi alanları: hazır 10 alan + panelden eklenenler (ad, ikon). Sırası ve aç/kapası
 * Sayfalar › Şehirler › Bölümler listesinde (PageSections "guide:{id}"); her şehir ayrıca kendi alanını açar/kapar.
 */
class CityGuideTopicController extends Controller
{
    public function create()
    {
        return view('admin.city-guides.topic-form', ['topic' => new CityGuideTopic(['icon' => 'sparkles', 'active' => true])]);
    }

    public function store(Request $request)
    {
        $topic = new CityGuideTopic();
        $this->fill($topic, $request);
        $topic->key = $this->uniqueKey($topic->getTranslation('name', 'en'));
        $topic->sort_order = (int) CityGuideTopic::max('sort_order') + 1;
        $topic->save();

        return redirect($this->back())->with('success', __('Alan eklendi. Şehirler sayfasının Bölümler listesinde en sona geldi; oradan yerini değiştirebilirsin.'));
    }

    public function edit(CityGuideTopic $topic)
    {
        return view('admin.city-guides.topic-form', ['topic' => $topic]);
    }

    public function update(Request $request, CityGuideTopic $topic)
    {
        $this->fill($topic, $request);
        $topic->save();

        return redirect($this->back())->with('success', __('Alan güncellendi.'));
    }

    public function destroy(CityGuideTopic $topic)
    {
        $topic->delete();

        return redirect($this->back())->with('success', __('Alan silindi; şehirlerdeki yazıları da silindi.'));
    }

    /**
     * Sıra ve aç/kapa Sayfalar › Şehirler › Bölümler'de: oraya dönülür (Sayfalar sadece admin'de; editör rehbere döner).
     */
    public static function back(): string
    {
        $page = auth()->user()?->isAdmin() ? \App\Models\Page::where('key', 'cities')->first() : null;

        return $page ? route('pages.sections', $page) : route('city-guides.index');
    }

    private function fill(CityGuideTopic $topic, Request $request): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:40'],
            'icon' => ['required', Rule::in(array_keys(FeatureIcons::ICONS))],
            'example' => ['nullable', 'string', 'max:160'],
        ], [], ['name' => __('alan adı')]);

        $topic->setTranslation('name', 'en', trim($data['name']));
        $topic->icon = $data['icon'];
        $topic->example = filled($data['example'] ?? null) ? trim($data['example']) : null;
    }

    private function uniqueKey(string $name): string
    {
        $base = Str::slug($name) ?: 'topic';
        $key = $base;
        for ($i = 2; CityGuideTopic::where('key', $key)->exists(); $i++) {
            $key = "{$base}-{$i}";
        }

        return $key;
    }
}
