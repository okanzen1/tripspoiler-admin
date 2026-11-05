<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CountrySeeder extends Seeder
{
    /**
     * Tüm ülkeleri ekler (varsa dokunmaz) ve eski sitedeki şehirleri taşır.
     */
    public function run(): void
    {
        foreach (require database_path('data/countries.php') as $code => $name) {
            Country::firstOrCreate(['code' => $code], ['name' => $name, 'slug' => Str::slug($name)]);
        }

        // Eski sitede aktif olanlar: Türkiye + İstanbul
        Country::where('code', 'TR')->update(['active' => true]);

        foreach ([['TR', 'Istanbul', true], ['GR', 'Athens', false], ['HU', 'Budapest', false]] as [$code, $name, $active]) {
            City::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['country_id' => Country::where('code', $code)->value('id'), 'name' => $name, 'active' => $active],
            );
        }
    }
}
