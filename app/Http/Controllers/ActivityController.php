<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\City;
use App\Models\AffiliatePartner;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Translator;
use App\Services\TranslatorService;

class ActivityController extends Controller
{
    public function autoTranslate(Request $request, TranslatorService $translator)
    {
        $translation = $translator->translateSingle(
            $request->text,
            $request->lang
        );

        return response()->json([
            'translation'=>$translation
        ]);
    }

    public function saveTranslation(Request $request)
    {
        $activity = Activity::findOrFail($request->activity_id);

        $activity->setTranslation(
            $request->field,
            $request->lang,
            $request->text
        );

        $activity->save();

        return response()->json([
            'success' => true
        ]);
    }

    protected array $productTypes = [
        'product' => 'Product',
        'pass' => 'Pass',
        'package' => 'Package',
    ];

    public function index(Request $request)
    {
        $cities = City::where('active', true)->orderBy('name')->get();

        $query = Activity::query()->orderBy('sort_order');

        // Varsayılan şehir = 1
        $cityId = (int) $request->get('city_id', 1);
        $query->where('city_id', $cityId);

        // Varsayılan durum = AKTİF (1)
        $status = (int) $request->get('status', 1);
        $query->where('status', $status);

        $search = $request->get('search');

        if ($search) {
            $query->where('name->en', 'like', "%{$search}%");
        }

        // MOST POPULAR FİLTRESİ
        $mostPopular = $request->get('most_popular');
        if ($mostPopular !== null && $mostPopular !== '') {
            $query->where('most_popular', (int) $mostPopular);
        }

        // ACTIVITY TYPE FİLTRESİ
        $activityType = $request->get('activity_type');
        if ($activityType !== null && $activityType !== '') {
            $query->where('activity_type', $activityType);
        }

        $activities = $query
            ->paginate(10)
            ->withQueryString();

        return view('admin.activities.index', compact(
            'activities',
            'cities',
            'cityId',
            'status',
            'mostPopular'
        ))->with('productTypes', $this->productTypes);
    }

    public function create()
    {
        $cities = City::orderBy('id')->where('active', true)->get();

        return view('admin.activities.create', compact('cities'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'city_id' => 'required|exists:cities,id',
        ]);

        $activity = Activity::create([
            'name' => $data['name'],
            'city_id' => $data['city_id'],
            'status' => $data['status'] ?? false,
        ]);

        return redirect()
            ->route('activities.edit', $activity)
            ->with('success', 'Activity created successfully.');
    }

    public function edit(string $id)
    {
        $activity = Activity::findOrFail($id);
        $affiliatePartners = AffiliatePartner::where('active', true)->orderBy('name')->get();
        $cities = City::where('active', true)->orderBy('id')->get();
        $languages = Translator::where('active',1)->where('code','!=','en')->pluck('code');

        return view(
            'admin.activities.edit',
            compact('activity', 'cities', 'affiliatePartners', 'languages')
        )->with('productTypes', $this->productTypes);
    }

    public function update(Request $request, string $id)
    {
        $activity = Activity::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'affiliate_id' => 'required|exists:affiliate_partners,id',
            'source_product_id' => 'required|integer',
            'affiliate_link' => 'nullable|url|max:255',
            'city_id' => 'required|exists:cities,id',
            'status' => 'required|boolean',
            'most_popular' => 'required|boolean',
            'sort_order' => 'nullable|integer',
            'activity_type' => 'required|in:' . implode(',', array_keys($this->productTypes)),
            'duration' => 'nullable|string|max:50',
            'audio_guide' => 'required|boolean',
            'description' => 'nullable|string',
        ]);

        $activity->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['slug']),
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'affiliate_id' => $data['affiliate_id'] ?? null,
            'affiliate_link' => $data['affiliate_link'] ?? null,
            'city_id' => $data['city_id'],
            'status' => $data['status'],
            'most_popular' => $data['most_popular'],
            'sort_order' => $data['sort_order'] ?? 0,
            'activity_type' => $data['activity_type'],
            'duration' => $data['duration'] ?? null,
            'audio_guide' => $data['audio_guide'],
            'description' => $data['description'],
            'source_product_id' => $data['source_product_id'],
        ]);

        return redirect()
            ->route('activities.edit', $activity)
            ->with('success', 'Aktivite güncellendi.');
    }

    public function destroy(string $id)
    {
        $activity = Activity::findOrFail($id);
        $activity->delete();

        return redirect()
            ->route('activities.index')
            ->with('success', 'Activity deleted successfully.');
    }

    public function toggleStatus(Activity $activity)
    {
        $activity->update([
            'status' => ! $activity->status,
        ]);

        return back()->with('success', 'Durum güncellendi.');
    }
}
