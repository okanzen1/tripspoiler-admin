<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Museum;
use Illuminate\Http\Request;

class MuseumController extends Controller
{
    public function index()
    {
        $museums = Museum::with(['city'])
            ->orderBy('sort_order')
            ->paginate(10);

        return view('admin.museums.index', compact('museums'));
    }

    public function create()
    {
        $cities = City::orderBy('name')->get();

        return view('admin.museums.create', compact('cities'));
    }

    public function store(Request $request)
    {
        if (auth()->user()?->role !== 'super_admin') {
            return back()->withErrors('Super admin dışındaki kullanıcılar ekleyemez.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'city_id' => 'required|exists:cities,id',
            'sort_order' => 'nullable|integer',
            'status' => 'boolean',
        ]);

        $museum = Museum::create([
            'name' => $data['name'],
            'city_id' => $data['city_id'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'status' => $data['status'] ?? false,
        ]);

        return redirect()
            ->route('museums.edit', $museum)
            ->with('success', 'Museum created');
    }

    public function edit(Museum $museum)
    {
        $museum->load('images');

        $cities = City::orderBy('name')->get();

        return view('admin.museums.edit', compact('museum', 'cities'));
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

        $museum->update([
            'name' => $data['name'],
            'city_id' => $data['city_id'] ?? null,
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

        $museum->images()->each(function ($image) {
            $image->delete();
        });

        return redirect()
            ->route('museums.index')
            ->with('success', 'Museum deleted');
    }
}
