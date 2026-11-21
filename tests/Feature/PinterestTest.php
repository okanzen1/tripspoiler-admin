<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\PinterestPin;
use App\Models\Setting;
use App\Models\User;
use App\Services\Pinterest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PinterestTest extends TestCase
{
    use RefreshDatabase;

    private function connect(): void
    {
        Setting::put('pinterest_app_id', '1234');
        Setting::put('pinterest_app_secret', Crypt::encryptString('secret'));
        Setting::put('pinterest_access_token', Crypt::encryptString('access'));
        Setting::put('pinterest_refresh_token', Crypt::encryptString('refresh'));
        Setting::put('pinterest_token_expires_at', (string) now()->addDays(20)->timestamp);
        Setting::put('pinterest_board_id', '987');
        Setting::put('pinterest_enabled', '1');
        Setting::put('pinterest_daily', '10');
    }

    private function blog(string $slug, array $extra = []): Blog
    {
        return Blog::create(['title' => ['en' => ucfirst($slug), 'tr' => 'TR ' . $slug], 'slug' => $slug, 'excerpt' => ['en' => 'Excerpt ' . $slug],
            'content' => ['en' => '<p>x</p>'], 'active' => true, 'published_at' => now()->subDays(3), 'cover_path' => 'blogs/2026/11/' . $slug . '.webp', ...$extra]);
    }

    public function test_scheduled_send_posts_published_blogs_in_order_once_and_respects_daily_limit(): void
    {
        $this->connect();
        Setting::put('pinterest_daily', '2');
        $this->travelTo(now()->setTime(21, 7)); // son saat: kalan hakkın hepsi gönderilir
        $older = $this->blog('older', ['published_at' => now()->subDays(10)]);
        $newer = $this->blog('newer');
        $third = $this->blog('third', ['published_at' => now()->subDay()]);
        $this->blog('draft', ['active' => false]);
        $this->blog('no-cover', ['cover_path' => null]);
        $this->blog('future', ['published_at' => now()->addDays(5)]);

        $n = 0;
        Http::fake(['api.pinterest.com/v5/pins' => function ($request) use (&$n) {
            return Http::response(['id' => 'pin' . ++$n]);
        }]);

        $this->artisan('pinterest:send')->assertSuccessful();
        $this->assertSame(['older', 'newer'], PinterestPin::orderBy('id')->get()->map->blog->pluck('slug')->all());

        Http::assertSent(fn ($r) => $r['board_id'] === '987' && $r['title'] === 'Older' && $r['description'] === 'Excerpt older'
            && $r['link'] === config('site.url') . '/en/blog/older' && str_ends_with($r['media_source']['url'], 'blogs/2026/11/older.webp'));

        // günlük sınır doldu: aynı gün tekrar çalışınca gönderilmez
        $this->artisan('pinterest:send');
        $this->assertSame(2, PinterestPin::count());

        // ertesi gün sıradaki gider; gönderilmiş olanlar bir daha gitmez
        $this->travelTo(now()->addDay()->setTime(21, 7));
        $this->artisan('pinterest:send');
        $this->assertSame(3, PinterestPin::count());
        $this->assertTrue($third->pinterestPins()->sent()->exists());
        $this->assertSame(0, Pinterest::queue()->count());
        $this->assertSame(1, $older->pinterestPins()->count());
    }

    public function test_nothing_is_sent_when_disabled_or_outside_hours(): void
    {
        $this->connect();
        $this->blog('one');
        Http::fake();

        $this->travelTo(now()->setTime(3, 7));
        $this->artisan('pinterest:send');
        Setting::put('pinterest_enabled', '0');
        $this->travelTo(now()->setTime(12, 7));
        $this->artisan('pinterest:send');

        Http::assertNothingSent();
        $this->assertSame(0, PinterestPin::count());
    }

    public function test_failed_pin_is_recorded_and_not_retried_automatically_until_requeued(): void
    {
        $this->connect();
        $this->travelTo(now()->setTime(21, 7));
        $blog = $this->blog('one');
        Http::fake(['api.pinterest.com/v5/pins' => Http::response(['message' => 'Invalid image'], 400)]);

        $this->artisan('pinterest:send');
        $pin = PinterestPin::first();
        $this->assertNull($pin->pin_id);
        $this->assertStringContainsString('Invalid image', $pin->error);
        $this->assertSame(0, Pinterest::queue()->count());

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post(route('pinterest.retry'))->assertRedirect();
        $this->assertSame(1, Pinterest::queue()->count());
        $this->assertTrue($blog->is(Pinterest::queue()->first()));
    }

    public function test_manual_send_never_resends_and_needs_a_cover(): void
    {
        $this->connect();
        $fresh = $this->blog('fresh');
        $sent = $this->blog('sent');
        $noCover = $this->blog('no-cover', ['cover_path' => null]);
        PinterestPin::create(['blog_id' => $sent->id, 'pin_id' => 'old', 'board_id' => '987']);
        Http::fake(['api.pinterest.com/v5/pins' => Http::response(['id' => 'new'])]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('blogs.edit', $fresh))->assertOk()->assertSee(route('pinterest.send', $fresh));
        $this->get(route('blogs.edit', $sent))->assertOk()->assertDontSee(route('pinterest.send', $sent))->assertSee(__('Pini aç'));
        $this->get(route('blogs.edit', $noCover))->assertOk()->assertDontSee(route('pinterest.send', $noCover));

        $this->post(route('pinterest.send', $fresh))->assertSessionHas('success');
        $this->post(route('pinterest.send', $sent))->assertSessionHas('error');
        $this->post(route('pinterest.send', $noCover))->assertSessionHas('error');
        $this->post(route('pinterest.send', $fresh))->assertSessionHas('error'); // ikinci kez de gitmez

        Http::assertSentCount(1);
        $this->assertTrue($fresh->pinterestPins()->first()->manual);
        $this->assertSame(['old'], $sent->pinterestPins()->pluck('pin_id')->all());
    }

    public function test_expired_token_is_refreshed_before_sending(): void
    {
        $this->connect();
        Setting::put('pinterest_token_expires_at', (string) now()->subMinute()->timestamp);
        $blog = $this->blog('one');
        Http::fake([
            'api.pinterest.com/v5/oauth/token' => Http::response(['access_token' => 'fresh', 'expires_in' => 2592000]),
            'api.pinterest.com/v5/pins' => Http::response(['id' => 'p1']),
        ]);

        app(Pinterest::class)->send($blog);

        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/oauth/token') && $r['grant_type'] === 'refresh_token' && $r['refresh_token'] === 'refresh');
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/pins') && $r->hasHeader('Authorization', 'Bearer fresh'));
        $this->assertSame('refresh', Crypt::decryptString(Setting::get('pinterest_refresh_token'))); // yeni gelmezse eskisi kalır
    }

    public function test_connect_flow_stores_encrypted_tokens_and_settings_page_lists_boards(): void
    {
        Setting::put('pinterest_app_id', '1234');
        Setting::put('pinterest_app_secret', Crypt::encryptString('secret'));
        $admin = User::factory()->admin()->create();

        $redirect = $this->actingAs($admin)->get(route('pinterest.connect'))->assertRedirect()->headers->get('Location');
        $this->assertStringStartsWith('https://www.pinterest.com/oauth/?', $redirect);
        parse_str(parse_url($redirect, PHP_URL_QUERY), $query);

        Http::fake([
            'api.pinterest.com/v5/oauth/token' => Http::response(['access_token' => 'acc', 'refresh_token' => 'ref', 'expires_in' => 2592000]),
            'api.pinterest.com/v5/user_account' => Http::response(['username' => 'tripspoiler']),
            'api.pinterest.com/v5/boards*' => Http::response(['items' => [['id' => '55', 'name' => 'Travel Guides']], 'bookmark' => null]),
        ]);

        // yanlış state reddedilir
        $this->get(route('pinterest.callback', ['code' => 'c', 'state' => 'wrong']))->assertSessionHas('error');
        $this->assertFalse(Pinterest::connected());

        $this->actingAs($admin)->get(route('pinterest.connect'));
        parse_str(parse_url($this->get(route('pinterest.connect'))->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->get(route('pinterest.callback', ['code' => 'c', 'state' => $query['state']]))->assertSessionHas('success');

        $this->assertTrue(Pinterest::connected());
        $this->assertNotSame('acc', Setting::get('pinterest_access_token'));
        $this->assertSame('tripspoiler', Setting::get('pinterest_account'));

        $this->get(route('pinterest.index'))->assertOk()->assertSee('Travel Guides')->assertSee('@tripspoiler');

        $this->put(route('pinterest.settings'), ['board_id' => '55', 'daily' => 10, 'enabled' => '1'])->assertSessionHas('success');
        $this->assertTrue(Pinterest::enabled());
        $this->assertSame('55', Pinterest::boardId());
    }

    public function test_editor_cannot_open_pinterest_settings(): void
    {
        $this->actingAs(User::factory()->create())->get(route('pinterest.index'))->assertRedirect();
    }
}
