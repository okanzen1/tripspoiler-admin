<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\Museum;
use Illuminate\Http\Request;

class MuseumController extends Controller
{
    public function index()
    {
        $museums = Museum::with(['city', 'country'])
            ->orderBy('sort_order')
            ->paginate(10);

        return view('admin.museums.index', compact('museums'));
    }

    public function create()
    {
        $cities = City::whereHas('country')->orderBy('name')->get();
        $countries = Country::orderBy('name')->get();

        return view('admin.museums.create', compact('cities', 'countries'));
    }

    public function store(Request $request)
    {
        if (auth()->user()?->role !== 'super_admin') {
            return back()->withErrors('Super admin dışındaki kullanıcılar ekleyemez.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'sort_order' => 'nullable|integer',
            'status' => 'boolean',
        ]);

        $city = null;
        $countryId = null;

        if (!empty($data['city_id'])) {
            $city = City::where('id', $data['city_id'])
                ->whereHas('country')
                ->first();

            if (!$city) {
                return back()->withErrors('Seçilen şehir geçerli bir ülkeye bağlı değil.');
            }

            $countryId = $city->country_id;
        }

        Museum::create([
            'name' => $data['name'],
            'city_id' => $city?->id,
            'country_id' => $countryId,
            'sort_order' => $data['sort_order'] ?? 0,
            'status' => $data['status'] ?? true,
        ]);

        return redirect()
            ->route('museums.index')
            ->with('success', 'Museum created');
    }

    public function edit(Museum $museum)
    {
        $museum->load('images');

        $cities = City::whereHas('country')->orderBy('name')->get();
        $countries = Country::orderBy('name')->get();

        return view('admin.museums.edit', compact('museum', 'cities', 'countries'));
    }

    public function update(Request $request, Museum $museum)
    {
        if (auth()->user()?->role !== 'super_admin') {
            return back()->withErrors('Super admin dışındaki kullanıcılar güncelleyemez.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'sort_order' => 'nullable|integer',
            'status' => 'boolean',
        ]);

        $city = null;
        $countryId = null;

        if (!empty($data['city_id'])) {
            $city = City::where('id', $data['city_id'])
                ->whereHas('country')
                ->first();

            if (!$city) {
                return back()->withErrors('Seçilen şehir geçerli bir ülkeye bağlı değil.');
            }

            $countryId = $city->country_id;
        }

        $museum->update([
            'name' => $data['name'],
            'city_id' => $city?->id,
            'country_id' => $countryId,
            'sort_order' => $data['sort_order'] ?? 0,
            'status' => $data['status'] ?? true,
        ]);

        return back()->with('success', 'Museum updated');
    }

    public function destroy(Museum $museum)
    {
        if (auth()->user()?->role !== 'super_admin') {
            return back()->withErrors('Super admin dışındaki kullanıcılar silemez.');
        }

        $museum->delete();

        return redirect()
            ->route('museums.index')
            ->with('success', 'Museum deleted');
    }
}
