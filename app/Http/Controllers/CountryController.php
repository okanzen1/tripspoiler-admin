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
}
