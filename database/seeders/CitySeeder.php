<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CitySeeder extends Seeder
{
    /**
     * GeoNames şehirlerini pasif olarak ekler (database/data/cities.tsv.gz).
     * Tekrar çalıştırmak güvenli: var olan şehirlere dokunmaz, elle eklenmiş
     * aynı adlı şehri GeoNames kaydıyla eşleştirir.
     */
    public function run(): void
    {
        $countryIds = Country::pluck('id', 'code');
        $knownGeonames = City::whereNotNull('geoname_id')->pluck('geoname_id')->flip();
        $usedSlugs = City::pluck('slug')->flip();
        $manual = City::whereNull('geoname_id')->get()->keyBy(fn (City $c) => "{$c->country_id}:{$c->slug}");

        // Nüfusa göre büyükten küçüğe: aynı adlı şehirlerde sade slug en kalabalığa düşsün (san-jose = ABD)
        $lines = array_filter(gzfile(database_path('data/cities.tsv.gz')), fn ($line) => $line[0] !== '#');
        $records = array_map(fn ($line) => explode("\t", rtrim($line, "\n")), $lines);
        usort($records, fn ($a, $b) => (int) $b[3] <=> (int) $a[3]);

        $rows = [];
        $now = now();

        foreach ($records as [$geonameId, $code, $name, $population, $lat, $lng]) {
            $countryId = $countryIds[$code] ?? null;

            if (!$countryId || isset($knownGeonames[$geonameId])) {
                continue;
            }

            $geo = ['geoname_id' => $geonameId, 'population' => $population, 'latitude' => $lat, 'longitude' => $lng];
            $baseSlug = Str::slug($name) ?: Str::slug(Str::ascii($name)) ?: "city-{$geonameId}";

            // Daha önce elle eklenmiş aynı şehir (ör. eski siteden gelen Istanbul): GeoNames bilgisini ekle
            if ($existing = $manual->pull("{$countryId}:{$baseSlug}")) {
                $existing->update($geo);
                continue;
            }

            // Aynı slug başka ülkede varsa ülke kodu, o da doluysa geoname id eklenir
            $slug = $baseSlug;
            foreach ([$baseSlug . '-' . Str::lower($code), $baseSlug . '-' . $geonameId] as $alternative) {
                if (!isset($usedSlugs[$slug])) {
                    break;
                }
                $slug = $alternative;
            }
            $usedSlugs[$slug] = true;

            $rows[] = $geo + [
                'country_id' => $countryId,
                'name' => $name,
                'slug' => $slug,
                'active' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($rows) === 1000) {
                DB::table('cities')->insert($rows);
                $rows = [];
            }
        }

        DB::table('cities')->insert($rows);
    }
}
