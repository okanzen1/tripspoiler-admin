<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\City;
use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Popülerlik: Wikipedia sayfası girilen aktivitenin son 30 gün okunması alınır, listede ve formda görünür */
class WikiViewsTest extends TestCase
{
    use RefreshDatabase;

    private City $city;

    protected function setUp(): void
    {
        parent::setUp();
        $country = Country::create(['code' => 'TR', 'name' => 'Türkiye', 'slug' => 'turkiye', 'active' => true]);
        $this->city = City::create(['country_id' => $country->id, 'name' => 'Istanbul', 'slug' => 'istanbul', 'active' => true]);
        Http::preventStrayRequests();
    }

    private function fakeWikipedia(): void
    {
        Http::fake([
            'en.wikipedia.org/w/api.php*' => fn ($request) => str_contains(urldecode($request->url()), 'Nowhere Page')
                ? Http::response(['query' => ['pages' => [['title' => 'Nowhere Page', 'missing' => true]]]])
                : Http::response(['query' => ['pages' => [['pageid' => 1, 'title' => 'Galata Tower']]]]),
            'wikimedia.org/api/rest_v1/metrics/pageviews/*' => Http::response(['items' => [['views' => 1200], ['views' => 800]]]),
        ]);
    }

    public function test_address_is_normalized_and_views_are_fetched_on_save(): void
    {
        $this->fakeWikipedia();

        $activity = Activity::create(['city_id' => $this->city->id, 'name' => ['en' => 'Galata'], 'slug' => 'galata', 'type' => 'product',
            'wiki_title' => 'https://en.wikipedia.org/wiki/Galata_Tower?x=1']);
        $this->app->terminate(); // sayı kayıttan sonra (yanıt gönderilince) alınır

        $activity->refresh();
        $this->assertSame('Galata Tower', $activity->wiki_title);
        $this->assertSame(2000, $activity->wiki_views);
        $this->assertNotNull($activity->wiki_views_at);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'Galata_Tower/daily/') && str_starts_with($request->header('User-Agent')[0] ?? '', 'TripSpoiler/'));

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('activities.index'))->assertOk()->assertSee('Wikipedia ilgisi')->assertSee('2.000');
        $this->get(route('activities.edit', $activity))->assertOk()->assertSee('Son 30 günde 2.000 okunma');

        // sayfa kaldırılınca sayı da boşalır
        $activity->update(['wiki_title' => '']);
        $this->assertNull($activity->fresh()->wiki_views);
    }

    public function test_missing_page_is_flagged_and_nightly_command_refreshes(): void
    {
        $this->fakeWikipedia();

        $missing = Activity::create(['city_id' => $this->city->id, 'name' => ['en' => 'X'], 'slug' => 'x', 'type' => 'product', 'wiki_title' => 'Nowhere Page']);
        $this->app->terminate();
        $this->assertNull($missing->fresh()->wiki_views);
        $this->assertNotNull($missing->fresh()->wiki_views_at);

        $found = Activity::create(['city_id' => $this->city->id, 'name' => ['en' => 'Y'], 'slug' => 'y', 'type' => 'product']);
        $found->forceFill(['wiki_title' => 'Galata Tower'])->saveQuietly();

        $this->artisan('wiki:refresh')->assertSuccessful();
        $this->assertSame(2000, $found->fresh()->wiki_views);
    }

    public function test_panel_form_saves_wiki_title(): void
    {
        $this->fakeWikipedia();
        $activity = Activity::create(['city_id' => $this->city->id, 'name' => ['en' => 'Galata'], 'slug' => 'galata', 'type' => 'product']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('activities.update', $activity), ['name' => 'Galata', 'slug' => 'galata', 'type' => 'product', 'city_id' => $this->city->id, 'wiki_title' => 'Galata Tower'])
            ->assertRedirect();

        $this->assertSame(2000, $activity->fresh()->wiki_views);
    }
}
