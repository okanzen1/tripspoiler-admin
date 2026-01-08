<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\City;
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
        $cities = City::with('country')->orderBy('id')->where('active', true)->get();

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
            'status' => 'nullable|boolean',
        ]);

        $city = City::where('id', $data['city_id'])->whereHas('country')->first();

        if (!$city) {
            return back()->withErrors('Seçilen şehir geçerli bir ülkeye bağlı değil.');
        }

        $activity = Activity::create([
            'name' => $data['name'],
            'city_id' => $city->id,
            'country_id' => $city->country_id,
            'status' => $data['status'] ?? 0,
        ]);

        return redirect()
            ->route('activities.edit', $activity)
            ->with('success', 'Activity created successfully.');
    }

    public function edit(string $id)
    {
        $activity = Activity::findOrFail($id);
        $cities = City::with('museums')->where('id', $activity->city_id)->where('active', true)->orderBy('id')->first();
        $museums = $cities->museums->where('status', true) ?? collect();

        return view('admin.activities.edit', compact('activity', 'museums'));
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
            'status' => 'required|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $activity->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['slug']),
            'museum_id' => $data['museum_id'] ?? null,
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
