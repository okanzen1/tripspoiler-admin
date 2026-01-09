<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\City;
use App\Models\AffiliatePartner;
use App\Models\Museum;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ActivityController extends Controller
{
    public function index()
    {
        $activities = Activity::orderBy('sort_order')->paginate(10);

        return view('admin.activities.index', compact('activities'));
    }

    public function create()
    {
        $cities = City::orderBy('id')->where('active', true)->get();

        return view('admin.activities.create', compact('cities'));
    }

    public function store(Request $request)
    {
        if (auth()->user()?->role !== 'super_admin') {
            return back()->withErrors('Super admin dışındaki kullanıcılar ekleyemez.');
        }

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
        $cities = City::with('museums')->where('active', true)->orderBy('id')->get();
        $museums = $cities->pluck('museums')->flatten()->where('status', true)
        ->where('city_id', $activity->city_id);

        return view('admin.activities.edit', compact('activity', 'museums', 'cities', 'affiliatePartners'));
    }

    public function update(Request $request, string $id)
    {
        if (auth()->user()?->role !== 'super_admin') {
            return back()->withErrors('Super admin dışındaki kullanıcılar güncelleyemez.');
        }

        $activity = Activity::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'museum_id' => 'nullable|exists:museums,id',
            'affiliate_id' => 'nullable|exists:affiliate_partners,id',
            'affiliate_link' => 'nullable|url|max:255',
            'status' => 'required|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        if (!empty($data['museum_id'])) {
            $museumID = Museum::where('id', $data['museum_id'])->value('city_id');
            if ($museumID !== $activity->city_id) {
                return back()
                    ->withErrors('Seçilen müze, aktivitenin şehri ile uyuşmuyor.')
                    ->withInput();
            }
        }

        $activity->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['slug']),
            'museum_id' => $data['museum_id'] ?? null,
            'affiliate_id' => $data['affiliate_id'] ?? null,
            'affiliate_link' => $data['affiliate_link'] ?? null,
            'status' => $data['status'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return redirect()
            ->route('activities.edit', $activity)
            ->with('success', 'Aktivite güncellendi.');
    }

    public function destroy(string $id)
    {
        if (auth()->user()?->role !== 'super_admin') {
            return back()->withErrors('Super admin dışındaki kullanıcılar silemez.');
        }

        $activity = Activity::findOrFail($id);
        $activity->delete();

        return redirect()
            ->route('activities.index')
            ->with('success', 'Activity deleted successfully.');
    }
}
