<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function index()
    {
        $cities = City::with('country')
            ->orderBy('id')
            ->paginate(10);

        return view('admin.cities.index', compact('cities'));
    }

    public function create()
    {
        $countries = Country::orderBy('id')->get();

        return view('admin.cities.create', compact('countries'));
    }

    public function store(Request $request)
    {
        if (auth()->user()?->role !== 'super_admin') {
            return back()->withErrors('Super admin dışındaki kullanıcılar ekleyemez.');
        }

        $data = $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
            'name'       => ['required', 'string', 'max:255'],
            'slug'       => ['required', 'string', 'max:255'],
            'active'     => ['required', 'in:0,1'],
        ]);

        City::create([
            'country_id' => $data['country_id'],
            'name'       => $data['name'],
            'slug'       => $data['slug'],
            'active'     => (bool) $data['active'],
        ]);

        return redirect()
            ->route('cities.index')
            ->with('success', 'Şehir oluşturuldu');
    }

    public function edit(City $city)
    {
        $city->load('images');

        $countries = Country::orderBy('id')->get();

        return view('admin.cities.edit', compact('city', 'countries'));
    }

    public function update(Request $request, City $city)
    {
        if (auth()->user()?->role !== 'super_admin') {
            return back()->withErrors('Super admin dışındaki kullanıcılar güncelleyemez.');
        }

        $data = $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
            'name'       => ['required', 'string', 'max:255'],
            'slug'       => ['required', 'string', 'max:255'],
            'active'     => ['required', 'in:0,1'],
        ]);

        $city->update([
            'country_id' => $data['country_id'],
            'name'       => $data['name'],
            'slug'       => $data['slug'],
            'active'     => (bool) $data['active'],
        ]);

        return redirect()
            ->route('cities.edit', $city)
            ->with('success', 'Şehir güncellendi');
    }

    public function destroy(City $city)
    {
        if (auth()->user()?->role !== 'super_admin') {
            return back()->withErrors('Super admin dışındaki kullanıcılar silemez.');
        }

        $city->delete();

        return redirect()
            ->route('cities.index')
            ->with('success', 'Şehir silindi');
    }
}
