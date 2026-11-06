<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Ülkeler ve şehirler tek ekranda: solda ülke listesi, sağda seçili ülkenin şehirleri.
 */
class LocationController extends Controller
{
    /** Arama yokken listelenen en kalabalık şehir sayısı (aktifler her zaman listede) */
    public const CITY_LIMIT = 100;

    public function index(Request $request)
    {
        // 250 ülke: arama/filtre tarayıcıda yapılır, aktifler üstte
        $countries = Country::sortByTurkishName(Country::withCount('cities')->get())->sortByDesc('active')->values();

        $selected = $countries->isEmpty()
            ? null
            : ($countries->firstWhere('id', $request->integer('country'))
                ?? $countries->firstWhere('active', true)
                ?? $countries->firstWhere('cities_count', '>', 0)
                ?? $countries->first());

        $search = trim((string) $request->get('q'));
        $status = in_array($request->get('status'), ['active', 'passive'], true) ? $request->get('status') : null;

        if ($selected) {
            $cities = $selected->cities()
                ->when($search, fn ($q) => $q->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', '%' . Str::slug($search) . '%')))
                ->when($status, fn ($q) => $q->where('active', $status === 'active'))
                ->orderByDesc('active')
                ->orderByRaw('population IS NULL, population DESC')
                ->orderBy('name')
                ->limit(self::CITY_LIMIT)
                ->get();

            $cityTotals = [
                'all' => $selected->cities_count,
                'active' => $selected->cities()->where('active', true)->count(),
            ];
        } else {
            $cities = collect();
            $cityTotals = ['all' => 0, 'active' => 0];
        }

        return view('admin.locations.index', [
            'countries' => $countries,
            'selected' => $selected,
            'cities' => $cities,
            'search' => $search,
            'status' => $status,
            'cityTotals' => $cityTotals,
            'searchKey' => fn (Country $c) => Str::lower(Str::ascii("{$c->name_tr} {$c->name} {$c->code}")),
            'activeCountryCount' => $countries->where('active', true)->count(),
            'visibleCityCount' => City::visible()->count(),
        ]);
    }
}
