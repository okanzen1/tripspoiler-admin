<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\City;
use App\Models\Country;
use App\Models\Page;
use App\Models\Partner;
use App\Models\User;

class ManagementController extends Controller
{
    public function index()
    {
        return view('admin.management.index', [
            'userCount' => User::count(),
            'roleCount' => count(Role::cases()),
            'activeCountryCount' => Country::active()->count(),
            'activeCityCount' => City::visible()->count(),
            'activePartnerCount' => Partner::active()->count(),
            'pagesMissingSeo' => Page::all()->reject->isSeoComplete()->count(),
            'activeLanguageCount' => \App\Models\Language::active()->count(),
            'redirectCount' => \App\Models\Redirect::count(),
            'heroImageSet' => filled(\App\Models\Setting::get('hero_image')),
        ]);
    }

    /** Sürükle-bırak ile sıralanabilen kart sayfaları → Setting anahtarı */
    public const ORDER_KEYS = ['management_order', 'site_settings_order', 'page_cards_order'];

    /**
     * Kart sayfasının sırası (resources/js/sortable-cards.js): key + kart anahtarları sırayla.
     */
    public function order(\Illuminate\Http\Request $request)
    {
        $data = $request->validate([
            'key' => ['required', \Illuminate\Validation\Rule::in(self::ORDER_KEYS)],
            'order' => ['required', 'array', 'max:50'],
            'order.*' => ['string', 'max:40', 'regex:/^[a-z-]+$/'],
        ]);
        \App\Models\Setting::put($data['key'], implode(',', array_unique($data['order'])));

        return response()->json(['ok' => true]);
    }

    /**
     * Kartları kaydedilmiş sıraya dizer; sırada olmayan (yeni) kartlar sona.
     *
     * @param  list<array{key: string}>  $cards
     */
    public static function sorted(array $cards, string $orderKey): array
    {
        $order = array_flip(array_filter(explode(',', (string) \App\Models\Setting::get($orderKey))));

        return collect($cards)->sortBy(fn ($card, $i) => $order[$card['key']] ?? 1000 + $i)->values()->all();
    }

    public function roles()
    {
        $counts = User::toBase()->selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role');

        return view('admin.management.roles', compact('counts'));
    }
}
