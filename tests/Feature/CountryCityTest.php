<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\User;
use Database\Seeders\CountrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CountryCityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CountrySeeder::class);
        $this->admin = User::factory()->admin()->create();
    }

    public function test_seeder_adds_all_countries_with_turkey_active(): void
    {
        $this->assertSame(250, Country::count());
        $this->assertSame(['TR'], Country::active()->pluck('code')->all());
        $this->assertTrue(City::where('slug', 'istanbul')->value('active'));
    }

    public function test_editor_cannot_manage_countries_or_cities(): void
    {
        $editor = User::factory()->create();
        $country = Country::where('code', 'IT')->first();

        $this->actingAs($editor)->get(route('locations.index'))->assertRedirect(route("analytics.index"));
        $this->actingAs($editor)->patch(route('countries.toggle', $country))->assertForbidden();
        $this->actingAs($editor)->get(route('cities.create'))->assertRedirect(route("analytics.index"));
        $this->assertFalse($country->fresh()->active);
    }

    public function test_locations_page_selects_country_and_lists_its_cities(): void
    {
        // Varsayılan: ilk aktif ülke (Türkiye) seçili
        $this->actingAs($this->admin)->get(route('locations.index'))
            ->assertOk()->assertSee('Türkiye')->assertSee('Istanbul')->assertDontSee('Athens');

        $greece = Country::where('code', 'GR')->first();
        $this->actingAs($this->admin)->get(route('locations.index', ['country' => $greece->id]))
            ->assertOk()->assertSee('Athens')->assertDontSee('/cities/istanbul');
    }

    public function test_country_search_keys_are_ascii_folded(): void
    {
        // Tarayıcıdaki arama data-search üzerinden çalışır: "turkiye" / "almanya" eşleşmeli
        $this->actingAs($this->admin)->get(route('locations.index'))
            ->assertSee('data-search="turkiye turkiye tr"', false)
            ->assertSee('data-search="almanya germany de"', false);
    }

    public function test_country_toggle_returns_json_for_ajax(): void
    {
        $italy = Country::where('code', 'IT')->first();

        $this->actingAs($this->admin)->patchJson(route('countries.toggle', $italy))
            ->assertOk()->assertJson(['active' => true]);

        $this->assertTrue($italy->fresh()->active);
    }

    public function test_admin_can_create_city_with_auto_slug(): void
    {
        $italy = Country::where('code', 'IT')->first();

        $this->actingAs($this->admin)->post(route('cities.store'), [
            'country_id' => $italy->id,
            'name' => 'Rome',
            'slug' => '',
            'active' => '1',
        ])->assertRedirect(route('locations.index', ['country' => $italy->id]))->assertSessionHas('success', fn ($m) => str_contains($m, 'pasif olduğu için'));

        $city = City::where('name', 'Rome')->first();
        $this->assertSame('rome', $city->slug);
        $this->assertTrue($city->active);
        $this->assertTrue($city->isHiddenByCountry());
    }

    public function test_city_slug_must_be_unique(): void
    {
        $this->actingAs($this->admin)->post(route('cities.store'), [
            'country_id' => Country::where('code', 'TR')->value('id'),
            'name' => 'İstanbul',
            'active' => '0',
        ])->assertSessionHasErrors('slug');
    }

    public function test_visible_scope_requires_city_and_country_active(): void
    {
        $this->assertSame(['istanbul'], City::visible()->pluck('slug')->all());

        Country::where('code', 'TR')->update(['active' => false]);
        $this->assertSame([], City::visible()->pluck('slug')->all());
    }

    public function test_admin_can_update_toggle_and_delete_city(): void
    {
        $athens = City::where('slug', 'athens')->first();

        $this->actingAs($this->admin)->put(route('cities.update', $athens), [
            'country_id' => $athens->country_id, 'name' => 'Athina', 'slug' => 'athens', 'active' => '0',
        ])->assertRedirect(route('locations.index', ['country' => $athens->country_id]));
        $this->assertSame('Athina', $athens->fresh()->name);

        $this->actingAs($this->admin)->patchJson(route('cities.toggle', $athens))->assertJson(['active' => true, 'warning' => true]);

        $this->actingAs($this->admin)->delete(route('cities.destroy', $athens))->assertRedirect(route('locations.index', ['country' => $athens->country_id]));
        $this->assertModelMissing($athens);
    }

    public function test_city_wizard_page_renders(): void
    {
        $this->actingAs($this->admin)->get(route('cities.create'))
            ->assertOk()->assertSee('data-wizard', false)->assertSee('Aktif ülkeler');
    }

    public function test_city_seeder_loads_geonames_and_keeps_existing_cities(): void
    {
        $this->seed(\Database\Seeders\CitySeeder::class);

        $istanbul = City::where('slug', 'istanbul')->first();
        $this->assertTrue($istanbul->active);                    // eski durum korunur
        $this->assertSame(745044, $istanbul->geoname_id);       // GeoNames ile eşleşti
        $this->assertSame(1, City::where('name', 'Istanbul')->count());

        $bursa = City::where('slug', 'bursa')->first();
        $this->assertSame('TR', $bursa->country->code);
        $this->assertFalse($bursa->active);

        // Aynı adlı şehirlerde sade slug en kalabalığa gider
        $this->assertSame('US', City::where('slug', 'san-jose')->first()->country->code);

        // Tekrar çalıştırmak kopya üretmez
        $count = City::count();
        $this->seed(\Database\Seeders\CitySeeder::class);
        $this->assertSame($count, City::count());
    }

    public function test_locations_search_finds_city_within_country(): void
    {
        $this->seed(\Database\Seeders\CitySeeder::class);
        $turkey = Country::where('code', 'TR')->first();

        $this->actingAs($this->admin)->get(route('locations.index', ['country' => $turkey->id, 'q' => 'bursa']))
            ->assertOk()->assertSee('Bursa')->assertSee('3,1 milyon nüfus')->assertDontSee('Ankara');

        // Türkiye: 81 ilin hepsi listede, ilçeler yok
        $this->assertSame(81, $turkey->cities()->count());
        $this->actingAs($this->admin)->get(route('locations.index', ['country' => $turkey->id]))
            ->assertSee('Ankara')->assertSee('Ardahan')->assertDontSee('Nilüfer')->assertDontSee('Esenyurt');
    }

    public function test_big_countries_show_most_populous_cities_first(): void
    {
        $this->seed(\Database\Seeders\CitySeeder::class);
        $usa = Country::where('code', 'US')->first();

        $this->actingAs($this->admin)->get(route('locations.index', ['country' => $usa->id]))
            ->assertSeeInOrder(['New York City', 'Los Angeles', 'Chicago'])
            ->assertSee('en kalabalık');
    }

    public function test_inline_slug_update_slugifies_and_rejects_duplicates(): void
    {
        $athens = City::where('slug', 'athens')->first();

        $this->actingAs($this->admin)->patchJson(route('cities.slug', $athens), ['slug' => 'Atina Şehri'])
            ->assertOk()->assertJson(['slug' => 'atina-sehri']);

        $this->actingAs($this->admin)->patchJson(route('cities.slug', $athens), ['slug' => 'istanbul'])
            ->assertUnprocessable()->assertJsonValidationErrors('slug');

        $this->assertSame('atina-sehri', $athens->fresh()->slug);
    }
}
