<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function toggle(Request $request, Country $country)
    {
        $country->update(['active' => !$country->active]);

        $message = __($country->active ? ':name aktif edildi.' : ':name pasif yapıldı.', ['name' => $country->name_tr]);

        return $request->expectsJson()
            ? response()->json(['active' => $country->active, 'message' => $message])
            : back()->with('success', $message);
    }

    /**
     * Ülkenin para birimi (sitede döviz kuru şeridinde kullanılır).
     */
    public function currency(Request $request, Country $country)
    {
        $data = $request->validate([
            'currency' => ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(Country::currencyOptions()))],
        ]);
        $country->update(['currency' => $data['currency']]);

        return back()->with('success', __(':name para birimi: :currency', ['name' => $country->name_tr, 'currency' => $data['currency']]));
    }
}
