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
        $cities = City::orderBy('name')->get();
        $countries = Country::orderBy('name')->get();

        return view('admin.museums.create', compact('cities', 'countries'));
    }

    public function store(Request $request)
    {
        if (auth()->user()?->role !== 'super_admin') {
            return back()->withErrors('Super admin dışındaki kullanıcılar güncelleyemez.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'city_id' => 'nullable|integer|exists:cities,id',
            'sort_order' => 'nullable|integer',
            'status' => 'boolean',
        ]);

        $countryId = City::where('id', $data['city_id'])->value('country_id') ?? null;

        if (!$countryId) {
            return back()->withErrors('City has no country assigned.');
        }

        Museum::create([
            'name' => ['en' => $data['name']],  // FAQ’deki gibi JSON
            'city_id' => $data['city_id'] ?? null,
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
        $cities = City::orderBy('name')->get();
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
            'city_id' => 'nullable|integer|exists:cities,id',
            'sort_order' => 'nullable|integer',
            'status' => 'boolean',
        ]);

        $countryId = City::where('id', $data['city_id'])->value('country_id') ?? null;

        if (!$countryId) {
            return back()->withErrors('City has no country assigned.');
        }

        $museum->update([
            'name' => ['en' => $data['name']], // JSON
            'city_id' => $data['city_id'] ?? null,
            'country_id' => $countryId,
            'sort_order' => $data['sort_order'] ?? 0,
            'status' => $data['status'] ?? true,
        ]);

        return back()->with('success', 'Museum updated');
    }

    public function destroy(Museum $museum)
    {
        if (auth()->user()?->role !== 'super_admin') {
            return back()->withErrors('Super admin dışındaki kullanıcılar güncelleyemez.');
        }

        $museum->delete();

        return redirect()
            ->route('museums.index')
            ->with('success', 'Museum deleted');
    }
}
