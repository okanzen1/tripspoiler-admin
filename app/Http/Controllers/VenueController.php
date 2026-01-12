<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use App\Models\City;
use App\Models\Museum;
use App\Models\AffiliatePartner;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VenueController extends Controller
{
    public function index()
    {
        $venues = Venue::orderBy('sort_order')->paginate(10);
        return view('admin.venues.index', compact('venues'));
    }

    public function create()
    {
        $cities = City::where('active', true)->orderBy('id')->get();
        return view('admin.venues.create', compact('cities'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()?->role === 'super_admin', 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'city_id' => 'required|exists:cities,id',
        ]);

        $venue = Venue::create([
            'name' => $data['name'],
            'city_id' => $data['city_id'],
            'status' => false,
        ]);

        return redirect()
            ->route('venues.edit', $venue)
            ->with('success', 'Venue created.');
    }

    public function edit(Venue $venue)
    {
        $cities = City::with('museums')->where('active', true)->get();
        $affiliatePartners = AffiliatePartner::where('active', true)->orderBy('name')->get();

        $museums = $cities->pluck('museums')
            ->flatten()
            ->where('status', true)
            ->where('city_id', $venue->city_id);

        return view('admin.venues.edit', compact(
            'venue',
            'cities',
            'museums',
            'affiliatePartners'
        ));
    }

    public function update(Request $request, Venue $venue)
    {
        abort_unless(auth()->user()?->role === 'super_admin', 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'museum_id' => 'nullable|exists:museums,id',
            'affiliate_id' => 'nullable|exists:affiliate_partners,id',
            'affiliate_link' => 'nullable|url',
            'status' => 'required|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $venue->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['slug']),
            'museum_id' => $data['museum_id'] ?? null,
            'affiliate_id' => $data['affiliate_id'] ?? null,
            'affiliate_link' => $data['affiliate_link'] ?? null,
            'status' => $data['status'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return back()->with('success', 'Venue updated.');
    }

    public function destroy(Venue $venue)
    {
        abort_unless(auth()->user()?->role === 'super_admin', 403);

        $venue->delete();

        return redirect()
            ->route('venues.index')
            ->with('success', 'Venue deleted.');
    }
}
