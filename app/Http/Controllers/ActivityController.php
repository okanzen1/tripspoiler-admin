<?php

namespace App\Http\Controllers;

use App\Enums\ActivityType;
use App\Http\Requests\ActivityRequest;
use App\Models\Activity;
use App\Models\AffiliateClick;
use App\Models\City;
use App\Models\Country;
use App\Support\FaqSync;
use App\Support\ReviewSync;
use App\Models\Partner;
use App\Services\ImageUploader;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityController extends Controller
{
    public function __construct(private ImageUploader $uploader) {}

    public function index(Request $request)
    {
        $search = trim((string) $request->get('search'));
        $status = in_array($request->get('status'), ['active', 'passive'], true) ? $request->get('status') : null;
        $cityId = $request->integer('city') ?: null;
        $partnerId = $request->integer('partner') ?: null;
        $type = ActivityType::tryFrom((string) $request->get('type'));
        $popular = in_array($request->get('popular'), ['yes', 'no'], true) ? $request->get('popular') : null;

        $activities = Activity::with(['city.country', 'partner', 'cover'])
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('name->en', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%")))
            ->when($status, fn ($q) => $q->where('active', $status === 'active'))
            ->when($cityId, fn ($q) => $q->where('city_id', $cityId))
            ->when($partnerId, fn ($q) => $q->where('partner_id', $partnerId))
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($popular, fn ($q) => $q->where('is_popular', $popular === 'yes'))
            ->orderBy('city_id')
            ->orderBy('sort_order')
            ->paginate(25)
            ->withQueryString();

        $clicks = AffiliateClick::whereIn('activity_id', $activities->pluck('id'))
            ->where('created_at', '>=', now()->subDays(30)->startOfDay())
            ->selectRaw('activity_id, COUNT(*) as total')->groupBy('activity_id')->pluck('total', 'activity_id');

        return view('admin.activities.index', [
            'activities' => $activities,
            'clicks' => $clicks,
            'search' => $search,
            'status' => $status,
            'cityId' => $cityId,
            'partnerId' => $partnerId,
            'type' => $type,
            'popular' => $popular,
            'filterPartners' => Partner::whereHas('activities')->orderBy('name')->pluck('name', 'id'),
            'filterCities' => City::with('country')->whereHas('activities')->orderBy('name')->get(),
            'activeCount' => Activity::active()->count(),
            'totalCount' => Activity::count(),
        ]);
    }

    public function create()
    {
        return view('admin.activities.form', $this->formData(new Activity(['type' => 'product', 'active' => false])));
    }

    public function store(ActivityRequest $request)
    {
        $activity = DB::transaction(function () use ($request) {
            $activity = new Activity();
            $this->fill($activity, $request);
            $activity->sort_order = (int) Activity::where('city_id', $activity->city_id)->max('sort_order') + 1;
            $activity->save();
            $this->syncImages($activity, $request);
            $this->syncFaqs($activity, $request);
            ReviewSync::sync($activity, $request->validated('reviews') ?? []);

            return $activity;
        });

        // Eklenen kaydın sayfasında kal: hemen diğer dillere çevrilebilsin
        return redirect()->route('activities.edit', $activity)->with('success', __('":name" eklendi.', ['name' => $activity->name]) . ' ' . __('Şimdi yukarıdaki Çeviriler kutusundan diğer dillere çevirebilirsin.'));
    }

    public function edit(Activity $activity)
    {
        return view('admin.activities.form', $this->formData($activity->load(['images', 'faqs', 'reviews'])));
    }

    public function update(ActivityRequest $request, Activity $activity)
    {
        DB::transaction(function () use ($request, $activity) {
            $oldCity = $activity->city_id;
            $this->fill($activity, $request);

            // Şehir değişirse yeni şehrin sonuna eklenir
            if ($activity->city_id !== $oldCity) {
                $activity->sort_order = (int) Activity::where('city_id', $activity->city_id)->max('sort_order') + 1;
            }

            $activity->save();
            $this->syncImages($activity, $request);
            $this->syncFaqs($activity, $request);
            ReviewSync::sync($activity, $request->validated('reviews') ?? []);
        });

        return redirect()->route('activities.edit', $activity)->with('success', __('Değişiklikler kaydedildi.'));
    }

    public function destroy(Activity $activity)
    {
        if ($activity->clicks()->exists()) {
            return back()->with('error', __('Bu aktivitenin tıklama geçmişi var; silmek yerine pasif yap.'));
        }

        // Görseller ActivityImage silinirken dosyalarıyla birlikte gider
        DB::transaction(function () use ($activity) {
            $activity->images->each->delete();
            $activity->delete();
        });

        return redirect()->route('activities.index')->with('success', __('":name" silindi.', ['name' => $activity->name]));
    }

    /**
     * Anasayfa ve şehir sayfasındaki "En popüler" bölümünde gösterilsin mi.
     */
    public function togglePopular(Request $request, Activity $activity)
    {
        $activity->update(['is_popular' => !$activity->is_popular]);
        $message = __($activity->is_popular ? '":name" popülerlere eklendi.' : '":name" popülerlerden çıkarıldı.', ['name' => $activity->name]);

        return $request->expectsJson()
            ? response()->json(['active' => $activity->is_popular, 'message' => $message])
            : back()->with('success', $message);
    }

    public function toggle(Request $request, Activity $activity)
    {
        $activity->update(['active' => !$activity->active]);
        $message = __($activity->active ? '":name" yayına alındı.' : '":name" yayından kaldırıldı.', ['name' => $activity->name]);

        return $request->expectsJson()
            ? response()->json(['active' => $activity->active, 'message' => $message])
            : back()->with('success', $message);
    }

    private function fill(Activity $activity, ActivityRequest $request): void
    {
        $data = $request->validated();
        $data['description'] = HtmlSanitizer::clean($data['description'] ?? null);

        $activity->fill(collect($data)->except(['name', 'description', 'meta_title', 'meta_description', 'images', 'remove_images', 'cover_image_id', 'faqs', 'reviews'])->all());

        // Çok dilli alanlar: sadece İngilizce yazılır, diğer diller korunur
        foreach (['name', 'description', 'meta_title', 'meta_description'] as $field) {
            filled($data[$field] ?? null)
                ? $activity->setTranslation($field, 'en', $data[$field])
                : $activity->forgetTranslation($field, 'en');
        }
    }

    /**
     * Görseller: işaretlenenler silinir, yeniler eklenir, seçilen kapak başa alınır (sort_order 0 = kapak).
     */
    private function syncImages(Activity $activity, ActivityRequest $request): void
    {
        $activity->images()->whereIn('id', (array) $request->input('remove_images', []))->get()->each->delete();

        foreach ($request->file('images', []) as $file) {
            $activity->images()->create([...$this->uploader->activity($file), 'sort_order' => 1000]);
        }

        $images = $activity->images()->get();
        $cover = $images->firstWhere('id', (int) $request->input('cover_image_id')) ?? $images->first();

        $images->sortBy(fn ($image) => [$image->isNot($cover), $image->sort_order, $image->id])->values()
            ->each(fn ($image, $index) => $image->update(['sort_order' => $index]));
    }

    /**
     * SSS'ler: formdaki sıra kaydedilir; formdan çıkarılanlar silinir. Soru/cevap İngilizce yazılır,
     * mevcut soruların diğer dil çevirileri ve yayın durumu korunur. Boş satırlar atlanır.
     */
    private function syncFaqs(Activity $activity, ActivityRequest $request): void
    {
        FaqSync::sync($activity->faqs(), $request->validated('faqs') ?? []);
    }

    private function formData(Activity $activity): array
    {
        // Şehir seçimi: aktif ülkelerin şehirleri (+ aktivitenin mevcut şehri)
        $cities = City::with('country')
            ->where(fn ($q) => $q->whereHas('country', fn ($q) => $q->where('active', true))->orWhere('id', $activity->city_id))
            ->orderByDesc('active')->orderBy('name')->get()
            ->groupBy(fn (City $city) => $city->country->flag . ' ' . $city->country->name_tr);

        return [
            'activity' => $activity,
            'cities' => $cities,
            'partners' => Partner::where('active', true)->orWhere('id', $activity->partner_id)->orderBy('name')->get(),
            'hasActiveCountry' => Country::active()->exists(),
        ];
    }
}
