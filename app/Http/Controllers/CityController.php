<?php

namespace App\Http\Controllers;

use App\Http\Requests\CityRequest;
use App\Models\City;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CityController extends Controller
{
    public function create(Request $request)
    {
        return view('admin.cities.create', [
            'countries' => $this->countryOptions(),
            'preselected' => Country::find($request->integer('country')),
        ]);
    }

    public function store(CityRequest $request)
    {
        $city = City::create($request->validated());

        return $this->backToCountry($city)->with('success', $this->savedMessage($city, ':name eklendi.'));
    }

    public function edit(City $city)
    {
        return view('admin.cities.edit', ['city' => $city, 'countries' => $this->countryOptions()]);
    }

    public function update(CityRequest $request, City $city)
    {
        $city->update($request->validated());

        return $this->backToCountry($city)->with('success', $this->savedMessage($city, ':name güncellendi.'));
    }

    public function destroy(City $city)
    {
        if ($city->activities()->exists()) {
            return $this->backToCountry($city)->with('error', __(':name silinemez: bağlı aktiviteleri var. Bunun yerine pasif yap.', ['name' => $city->name]));
        }

        $city->delete();

        return $this->backToCountry($city)->with('success', __(':name silindi.', ['name' => $city->name]));
    }

    private function backToCountry(City $city)
    {
        return redirect()->route('locations.index', ['country' => $city->country_id]);
    }

    public function toggle(Request $request, City $city)
    {
        $city->update(['active' => !$city->active]);

        $message = __($city->active ? ':name aktif edildi.' : ':name pasif yapıldı.', ['name' => $city->name]);
        if ($city->isHiddenByCountry()) {
            $message .= ' ' . __('Ama :country pasif olduğu için sitede görünmeyecek.', ['country' => $city->country->name_tr]);
        }

        return $request->expectsJson()
            ? response()->json(['active' => $city->active, 'message' => $message, 'warning' => $city->isHiddenByCountry()])
            : back()->with('success', $message);
    }

    /**
     * Listeden URL adını (slug) doğrudan düzenleme.
     */
    public function updateSlug(Request $request, City $city)
    {
        $request->merge(['slug' => Str::slug((string) $request->input('slug'))]);
        $data = $request->validate(
            ['slug' => ['required', 'max:120', Rule::unique('cities')->ignore($city)]],
            ['slug.unique' => __('Bu URL adı başka bir şehirde kullanılıyor.'), 'slug.required' => __('URL adı boş olamaz.')],
        );

        $city->update($data);

        return response()->json(['slug' => $city->slug, 'message' => __(':name URL adı: :path', ['name' => $city->name, 'path' => '/cities/' . $city->slug])]);
    }

    /**
     * Seçim listesi için ülkeler: önce aktifler, sonra diğerleri (Türkçe ada göre).
     */
    private function countryOptions()
    {
        return Country::sortByTurkishName(Country::all())->groupBy(fn (Country $c) => $c->active ? 'active' : 'passive');
    }

    private function savedMessage(City $city, string $verb): string
    {
        $message = __($verb, ['name' => $city->name]);

        return $city->isHiddenByCountry()
            ? $message . ' ' . __('Not: :country pasif olduğu için şehir sitede görünmeyecek.', ['country' => $city->country->name_tr])
            : $message;
    }
}
