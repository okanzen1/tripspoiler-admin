<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Blog;
use App\Models\City;
use App\Models\Country;
use App\Models\Setting;
use App\Models\User;
use App\Services\SearchConsole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SearchConsoleTest extends TestCase
{
    use RefreshDatabase;

    private function keyJson(): string
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $pem);

        return json_encode(['type' => 'service_account', 'client_email' => 'panel@tripspoiler.iam.gserviceaccount.com', 'private_key' => $pem, 'project_id' => 'x']);
    }

    private function fakeGoogle(array $pages, array $queries): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            'searchconsole.googleapis.com/webmasters/v3/sites' => Http::response(['siteEntry' => [
                ['siteUrl' => 'sc-domain:tripspoiler.com', 'permissionLevel' => 'siteRestrictedUser'],
                ['siteUrl' => 'https://other.com/', 'permissionLevel' => 'siteUnverifiedUser'],
            ]]),
            'searchconsole.googleapis.com/webmasters/v3/sites/*/searchAnalytics/query' => function ($request) use ($pages, $queries) {
                return Http::response(['rows' => match ($request['dimensions']) {
                    [] => [['clicks' => 120, 'impressions' => 9000, 'ctr' => 0.0133, 'position' => 14.2]],
                    ['page'] => $pages,
                    ['query', 'page'] => $queries,
                }]);
            },
        ]);
    }

    public function test_connect_pick_site_and_see_opportunities_with_edit_links(): void
    {
        $site = config('site.url');
        $country = Country::create(['code' => 'IT', 'name' => 'Italy', 'slug' => 'italy', 'active' => true]);
        $rome = City::create(['country_id' => $country->id, 'name' => 'Rome', 'slug' => 'rome', 'active' => true]);
        $blog = Blog::create(['title' => ['en' => 'Rome guide'], 'slug' => 'rome-guide', 'content' => ['en' => '<p>x</p>']]);
        $blog->forceFill(['slugs' => ['tr' => 'roma-rehberi']])->saveQuietly();
        $activity = Activity::create(['city_id' => $rome->id, 'name' => ['en' => 'Colosseum'], 'slug' => 'colosseum-ticket', 'type' => 'product']);

        $this->fakeGoogle(
            pages: [
                ['keys' => ["$site/tr/blog/roma-rehberi"], 'clicks' => 3, 'impressions' => 900, 'ctr' => 0.0033, 'position' => 6.1],
                ['keys' => ["$site/en/activities/colosseum-ticket"], 'clicks' => 40, 'impressions' => 2000, 'ctr' => 0.02, 'position' => 11.5],
                ['keys' => ["$site/en/cities/rome"], 'clicks' => 2, 'impressions' => 300, 'ctr' => 0.0066, 'position' => 25],
            ],
            queries: [
                ['keys' => ['roma gezi rehberi', "$site/tr/blog/roma-rehberi"], 'clicks' => 1, 'impressions' => 500, 'ctr' => 0.002, 'position' => 5.2],
                ['keys' => ['colosseum tickets', "$site/en/activities/colosseum-ticket"], 'clicks' => 30, 'impressions' => 600, 'ctr' => 0.05, 'position' => 4],
                ['keys' => ['colosseum tickets', "$site/en/cities/rome"], 'clicks' => 0, 'impressions' => 40, 'ctr' => 0, 'position' => 30],
            ],
        );
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('search-console.index'))->assertOk()->assertSee(__('Google hizmet hesabı anahtarı'));
        $this->put(route('search-console.credentials'), ['credentials' => '{"foo":1}'])->assertSessionHasErrors('credentials');
        $this->put(route('search-console.credentials'), ['credentials' => $this->keyJson()])->assertSessionHas('success');
        $this->assertTrue(SearchConsole::connected());
        $this->assertNotSame(null, Setting::get('gsc_credentials'));
        $this->assertStringNotContainsString('PRIVATE KEY', (string) Setting::get('gsc_credentials')); // şifreli

        $this->get(route('search-console.index'))->assertSee('sc-domain:tripspoiler.com')->assertDontSee('https://other.com/')
            ->assertSee('panel@tripspoiler.iam.gserviceaccount.com');

        $this->put(route('search-console.site'), ['site' => 'sc-domain:tripspoiler.com'])->assertSessionHas('success');
        $this->assertSame(6, DB::table('search_console_rows')->count());
        $this->assertSame(120, SearchConsole::totals()['clicks']);
        Http::assertSent(fn ($r) => str_contains($r->url(), rawurlencode('sc-domain:tripspoiler.com') . '/searchAnalytics/query') && $r->hasHeader('Authorization', 'Bearer tok'));

        // fırsatlar: ilk 10'da, en az 20 gösterim, oran %2'nin altında
        $this->get(route('search-console.index'))->assertOk()
            ->assertSee('roma gezi rehberi')->assertDontSee('colosseum tickets')
            ->assertSee(route('blogs.edit', $blog), false);
        // ilk sayfaya yakın: 8–20. sıra (sayfa toplamı)
        $this->get(route('search-console.index', ['tab' => 'yakin']))->assertSee('colosseum-ticket')->assertSee(route('activities.edit', $activity), false)
            ->assertDontSee('roma-rehberi');
        // aramalar: aynı arama birden fazla sayfada → tek satır
        $this->get(route('search-console.index', ['tab' => 'aramalar', 'search' => 'colosseum']))->assertSee('colosseum tickets')->assertSee(__(':count sayfada', ['count' => 2]));
        $this->get(route('search-console.index', ['tab' => 'sayfalar']))->assertSee(route('city-guides.show', $rome), false);

        // adresi değişmiş sayfa: yönlendirmeden yeni kayda bağlanır, "eski adres" etiketi çıkar
        \App\Models\Redirect::create(['from_path' => '/fr/activities/old-colosseum', 'to_path' => '/fr/activities/colosseum-ticket']);
        DB::table('search_console_rows')->insert(['query' => null, 'page' => "$site/fr/activities/old-colosseum", 'page_hash' => sha1('o'), 'clicks' => 0, 'impressions' => 5, 'ctr' => 0, 'position' => 9]);
        $this->get(route('search-console.index', ['tab' => 'yakin', 'search' => 'old-colosseum']))
            ->assertSee(route('activities.edit', $activity), false)->assertSee(__('eski adres → yönlendiriliyor'));
    }

    public function test_daily_sync_and_permission_error_is_shown(): void
    {
        Setting::put('gsc_site', 'sc-domain:tripspoiler.com');
        SearchConsole::saveCredentials($this->keyJson());
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'tok']),
            'searchconsole.googleapis.com/*' => Http::response(['error' => ['message' => 'User does not have sufficient permission']], 403),
        ]);

        $this->artisan('gsc:sync')->assertSuccessful();
        $this->assertStringContainsString('kullanıcı olarak ekle', (string) Setting::get('gsc_error'));

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('search-console.index'))->assertOk()->assertSee('sufficient permission');
        $this->get(route('analytics.index'))->assertSee(route('search-console.index'), false); // menüde
    }

    public function test_disconnect_and_editor_access(): void
    {
        SearchConsole::saveCredentials($this->keyJson());
        DB::table('search_console_rows')->insert(['query' => null, 'page' => 'x', 'page_hash' => sha1('x')]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->delete(route('search-console.disconnect'))->assertSessionHas('success');
        $this->assertFalse(SearchConsole::connected());
        $this->assertSame(0, DB::table('search_console_rows')->count());

        $this->actingAs(User::factory()->create())->get(route('search-console.index'))->assertRedirect();
    }
}
