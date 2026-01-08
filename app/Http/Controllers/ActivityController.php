<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\City;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index()
    {
        $activities = Activity::orderBy('sort_order')->paginate(10);

        return view('admin.activities.index', compact('activities'));
    }

    public function create()
    {
        $cities = City::with('country')->orderBy('id')->get();

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

        Activity::create([
            'name' => $data['name'],
            'city_id' => $data['city_id'],
            'country_id' => $city->country_id,
            'status' => $data['status'] ?? 0,
        ]);

        return redirect()
            ->route('activities.index')
            ->with('success', 'Activity created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
    }
}
