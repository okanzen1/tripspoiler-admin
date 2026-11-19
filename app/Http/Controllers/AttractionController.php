<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Attraction;
use App\Models\City;
use App\Services\ImageUploader;
use App\Support\FaqSync;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Yer sayfaları (sitede /{dil}/attractions/{slug}): bir turistik yerin bütün biletleri, ziyaret bilgileri ve SSS'leri.
 * Metinler İngilizce yazılır; Çeviriler'den 12 dile çevrilir.
 */
class AttractionController extends Controller
{
    private const TEXT = ['name', 'summary', 'opening_hours', 'closed_days', 'getting_there', 'dress_code', 'good_to_know', 'meta_title', 'meta_description'];

    public function __construct(private ImageUploader $uploader) {}

    public function index(Request $request)
    {
        return view('admin.attractions.index', [
            'attractions' => Attraction::with('city')->withCount('activities')->orderBy('city_id')->ordered()->get(),
        ]);
    }

    public function create()
    {
        return $this->form(new Attraction(['active' => false]));
    }

    public function edit(Attraction $attraction)
    {
        return $this->form($attraction->load('faqs'));
    }

    /** Listede Yayında/Kapalı anahtarı: sayfa yenilenmeden (JSON) ya da normal formla. */
    public function toggle(Request $request, Attraction $attraction)
    {
        $attraction->update(['active' => !$attraction->active]);
        $name = $attraction->getTranslation('name', 'en', false);
        $message = __($attraction->active ? '":name" yayına alındı.' : '":name" yayından kaldırıldı.', ['name' => $name]);

        return $request->expectsJson()
            ? response()->json(['active' => $attraction->active, 'message' => $message])
            : back()->with('success', $message);
    }

    private function form(Attraction $attraction)
    {
        return view('admin.attractions.form', [
            'attraction' => $attraction,
            'cities' => City::where('active', true)->orderBy('name')->get(),
            'activities' => Activity::with('city')->orderBy('city_id')->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $attraction = new Attraction();
        $this->fill($attraction, $request);
        $attraction->sort_order = (int) Attraction::where('city_id', $attraction->city_id)->max('sort_order') + 1;
        $attraction->save();
        FaqSync::sync($attraction->faqs(), $request->input('faqs') ?? []);

        return redirect()->route('attractions.edit', $attraction)->with('success', __('Yer eklendi. Şimdi yukarıdan diğer dillere çevirebilirsin.'));
    }

    public function update(Request $request, Attraction $attraction)
    {
        $this->fill($attraction, $request);
        $attraction->save();
        FaqSync::sync($attraction->faqs(), $request->input('faqs') ?? []);

        return redirect()->route('attractions.edit', $attraction)->with('success', __('Yer kaydedildi.'));
    }

    public function destroy(Attraction $attraction)
    {
        $this->uploader->delete($attraction->image_path);
        $attraction->delete();

        return redirect()->route('attractions.index')->with('success', __('Yer silindi.'));
    }

    private function fill(Attraction $attraction, Request $request): void
    {
        $data = $request->validate([
            'city_id' => ['required', 'exists:cities,id'],
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9-]*$/', Rule::unique('attractions', 'slug')->ignore($attraction->id)],
            'summary' => ['nullable', 'string', 'max:400'],
            'content' => ['nullable', 'string', 'max:100000'],
            'opening_hours' => ['nullable', 'string', 'max:500'],
            'closed_days' => ['nullable', 'string', 'max:300'],
            'getting_there' => ['nullable', 'string', 'max:800'],
            'dress_code' => ['nullable', 'string', 'max:300'],
            'good_to_know' => ['nullable', 'string', 'max:3000'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:170'],
            'official_url' => ['nullable', 'url:https,http', 'max:500'],
            'activity_ids' => ['array'],
            'activity_ids.*' => ['integer', 'exists:activities,id'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:' . SiteSettingsController::MAX_KB],
            'remove_image' => ['boolean'],
            'active' => ['boolean'],
            ...FaqSync::rules(),
        ], FaqSync::messages(), ['name' => __('ad'), 'official_url' => __('resmi site')]);

        $attraction->city_id = (int) $data['city_id'];
        $attraction->slug = ($data['slug'] ?? '') !== '' ? $data['slug'] : ($attraction->slug ?: Str::slug($data['name']));
        $attraction->official_url = $data['official_url'] ?? null;
        $attraction->active = (bool) ($data['active'] ?? false);
        $attraction->activity_ids = $data['activity_ids'] ?? [];

        foreach (self::TEXT as $field) {
            $value = trim((string) ($data[$field] ?? ''));
            if ($field === 'good_to_know') {
                $value = implode("\n", array_filter(array_map(fn ($l) => trim(preg_replace('/^\s*(?:[-•*·]|\d+[.)])\s*/u', '', $l)), preg_split('/\R/u', strip_tags($value))), 'strlen'));
            }
            $value !== '' ? $attraction->setTranslation($field, 'en', $value) : ($attraction->exists ? $attraction->forgetTranslations($field, asNull: true) : null);
        }
        $content = HtmlSanitizer::clean($data['content'] ?? null);
        $content ? $attraction->setTranslation('content', 'en', $content) : ($attraction->exists ? $attraction->forgetTranslations('content', asNull: true) : null);

        if ($request->hasFile('image')) {
            $attraction->image_path = $this->uploader->cover($request->file('image'), replace: $attraction->image_path);
        } elseif ($request->boolean('remove_image') && $attraction->image_path) {
            $this->uploader->delete($attraction->image_path);
            $attraction->image_path = null;
        }
    }
}
