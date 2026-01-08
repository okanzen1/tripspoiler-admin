<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function index()
    {
        $countries = Country::orderBy('id')->paginate(10);

        return view('admin.countries.index', compact('countries'));
    }

    public function create()
    {
        return view('admin.countries.create');
    }

    public function store(Request $request)
    {
        if (auth()->user()?->role !== 'super_admin') {
            return back()->withErrors('Super admin dışındaki kullanıcılar ekleyemez.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        Country::create([
            'name' => $data['name'],
            'active' => $request->has('active'),
        ]);

        return redirect()
            ->route('countries.index')
            ->with('success', 'Ülke eklendi');
    }

    public function edit(Country $country)
    {
        return view('admin.countries.edit', compact('country'));
    }

    public function update(Request $request, Country $country)
    {
        if (auth()->user()?->role !== 'super_admin') {
            return back()->withErrors('Super admin dışındaki kullanıcılar güncelleyemez.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $country->update([
            'name' => $data['name'],
            'active' => $request->has('active'),
        ]);

        return redirect()
            ->route('countries.edit', $country)
            ->with('success', 'Ülke güncellendi');
    }

    public function destroy(Country $country)
    {
        if (auth()->user()?->role !== 'super_admin') {
            return back()->withErrors('Super admin dışındaki kullanıcılar silemez.');
        }

        $country->delete();

        return redirect()
            ->route('countries.index')
            ->with('success', 'Ülke silindi');
    }
}
