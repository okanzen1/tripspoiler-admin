<?php

namespace Tests\Feature;

use App\Models\AffiliateClick;
use App\Models\User;
use App\Models\Visit;
use App\Services\Analytics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Date::setTestNow('2026-10-31 15:00:00');
    }

    private function visit(string $visitor, string $session, string $path, string $at, array $extra = []): void
    {
        Visit::create(['visitor_id' => $visitor, 'session_id' => $session, 'path' => $path, 'created_at' => $at] + $extra);
    }

    private function click(string $visitor, string $path, string $at, string $partner = 'getyourguide', array $extra = []): void
    {
        AffiliateClick::create([
            'visitor_id' => $visitor, 'session_id' => 's', 'partner' => $partner,
            'path' => $path, 'target_url' => 'https://example.com', 'created_at' => $at,
        ] + $extra);
    }

    public function test_editor_and_admin_can_view_analytics(): void
    {
        $this->actingAs(User::factory()->create())->get(route('analytics.index'))->assertOk()->assertSee('Henüz veri yok');
        $this->actingAs(User::factory()->admin()->create())->get(route('analytics.index'))->assertOk();
    }

    public function test_kpis_compare_with_previous_period(): void
    {
        // Bu dönem (son 7 gün): 2 ziyaretçi, 3 görüntüleme, 1 tıklayan
        $this->visit('a', 's1', '/x', '2026-10-30 10:00:00', ['referrer_host' => 'google.com']);
        $this->visit('a', 's1', '/y', '2026-10-30 10:05:00');
        $this->visit('b', 's2', '/x', '2026-10-31 09:00:00');
        $this->click('a', '/x', '2026-10-30 10:01:00');
        $this->click('a', '/y', '2026-10-30 10:06:00', 'viator');

        // Önceki dönem: 1 ziyaretçi, 1 tıklama
        $this->visit('c', 's3', '/x', '2026-10-20 10:00:00');
        $this->click('c', '/x', '2026-10-20 10:01:00');

        $kpis = (new Analytics(7))->kpis();

        $this->assertSame(2, $kpis['visitors']['value']);
        $this->assertSame(3, $kpis['views']['value']);
        $this->assertSame(2, $kpis['clicks']['value']);
        $this->assertSame(50.0, $kpis['ctr']['value']);
        $this->assertSame(100.0, $kpis['clicks']['change']);   // 1 → 2
        $this->assertSame(100.0, $kpis['visitors']['change']); // 1 → 2
    }

    public function test_daily_series_fills_empty_days(): void
    {
        $this->visit('a', 's1', '/x', '2026-10-31 10:00:00');

        $daily = (new Analytics(7))->daily();

        $this->assertCount(7, $daily['labels']);
        $this->assertSame([0, 0, 0, 0, 0, 0, 1], $daily['visitors']);
    }

    public function test_sources_count_landing_page_of_each_session(): void
    {
        $this->visit('a', 's1', '/x', '2026-10-30 10:00:00', ['referrer_host' => 'google.com']);
        $this->visit('a', 's1', '/y', '2026-10-30 10:05:00'); // aynı oturum, kaynak yok → sayılmamalı
        $this->visit('b', 's2', '/x', '2026-10-31 09:00:00');

        $sources = (new Analytics(7))->sources()->pluck('total', 'label');

        $this->assertSame(['google.com' => 1, 'Doğrudan' => 1], $sources->all());
    }

    public function test_top_pages_ranked_by_clicks(): void
    {
        $this->visit('a', 's1', '/popular', '2026-10-30 10:00:00');
        $this->visit('b', 's2', '/clicky', '2026-10-30 11:00:00');
        $this->visit('c', 's3', '/popular', '2026-10-30 12:00:00');
        $this->click('b', '/clicky', '2026-10-30 11:01:00');

        $pages = (new Analytics(7))->topPages();

        $this->assertSame('/clicky', $pages[0]->path);
        $this->assertSame(100.0, $pages[0]->ctr);
        $this->assertSame(2, $pages[1]->views);
    }

    public function test_demo_command_creates_and_clears_only_demo_rows(): void
    {
        $this->visit('real', 's', '/x', '2026-10-30 10:00:00');

        $this->artisan('analytics:demo', ['--days' => 2])->assertSuccessful();
        $this->assertTrue(Visit::where('visitor_id', 'like', 'demo_%')->exists());

        $this->artisan('analytics:demo', ['--clear' => true])->assertSuccessful();
        $this->assertSame(['real'], Visit::pluck('visitor_id')->all());
    }

    public function test_settings_page_blocks_ips_and_purges_their_records(): void
    {
        $admin = User::factory()->admin()->create();
        $hash = \App\Support\Privacy::hash('81.214.164.227');
        $this->visit('a', 's1', '/x', '2026-10-30 10:00:00', ['ip' => '81.214.164.227', 'ip_hash' => $hash]);
        $this->visit('b', 's2', '/y', '2026-10-30 10:00:00', ['ip' => '1.1.1.1', 'ip_hash' => \App\Support\Privacy::hash('1.1.1.1')]);

        $this->actingAs($admin)->get(route('analytics-settings.index'))->assertOk()->assertSee('İstenmeyen IP');

        // geçersiz IP reddedilir, geçerliler kaydedilir
        $this->actingAs($admin)->put(route('analytics-settings.blocked'), ['ips' => "81.214.164.227\nbozuk"])->assertSessionHasErrors('ips');
        $this->actingAs($admin)->put(route('analytics-settings.blocked'), ['ips' => "81.214.164.227, 81.214.164.227\n"])->assertSessionHasNoErrors();
        $this->assertSame(['81.214.164.227'], \App\Support\AnalyticsIps::blocked());
        $this->assertSame(1, \App\Support\AnalyticsIps::counts(['81.214.164.227'])['81.214.164.227']);

        // eski kayıtlar silinir, diğer ziyaretçiye dokunulmaz
        $this->actingAs($admin)->delete(route('analytics-settings.purge'))->assertSessionHasNoErrors();
        $this->assertSame(0, Visit::where('ip_hash', $hash)->count());
        $this->assertSame(1, Visit::count());

        // editör ayar sayfasını açamaz
        $this->actingAs(User::factory()->create())->get(route('analytics-settings.index'))->assertRedirect(); // yetkisiz: geri yönlendirilir
    }

    public function test_custom_date_range_and_visitor_day_filter(): void
    {
        $this->visit('a', 's1', '/x', '2026-10-20 10:00:00');
        $this->visit('b', 's2', '/y', '2026-10-25 10:00:00');

        $range = new Analytics(30, \Carbon\CarbonImmutable::parse('2026-10-25'), \Carbon\CarbonImmutable::parse('2026-10-20'));
        $this->assertTrue($range->custom);
        $this->assertSame(6, $range->days); // ters verilse de 20–25 arası
        $this->assertSame(2, $range->kpis()['visitors']['value']);

        $this->assertSame(['b'], Analytics::visitors(['from' => '2026-10-25', 'to' => '2026-10-25'])->map(fn ($v) => $v->last->visitor_id)->all());
        $this->assertCount(2, Analytics::visitors(['from' => '2026-10-20', 'to' => '2026-10-25']));

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('analytics.index', ['from' => '2026-10-20', 'to' => '2026-10-25']))->assertOk()->assertSee('6 gün');
        $this->actingAs($admin)->get(route('analytics.live', ['from' => '2026-10-25', 'to' => '2026-10-25']))->assertOk();
    }

    public function test_one_filter_bar_applies_device_and_country_to_every_number(): void
    {
        $this->visit('a', 's1', '/x', '2026-10-31 10:00:00', ['device' => 'mobile', 'country' => 'TR']);
        $this->visit('b', 's2', '/y', '2026-10-31 11:00:00', ['device' => 'desktop', 'country' => 'US']);
        $this->click('a', '/x', '2026-10-31 10:01:00', extra: ['device' => 'mobile', 'country' => 'TR']);

        // bugün (range=1), yalnızca telefon
        $mobile = new Analytics(1, filters: ['device' => 'mobile']);
        $this->assertSame(1, $mobile->kpis()['visitors']['value']);
        $this->assertSame(1, $mobile->kpis()['clicks']['value']);
        $this->assertSame(0, (new Analytics(1, filters: ['country' => 'US']))->kpis()['clicks']['value']);
        $this->assertSame(2, (new Analytics(1))->kpis()['visitors']['value']);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('analytics.index', ['range' => 1, 'device' => 'mobile', 'country' => 'TR']))->assertOk()->assertSee('Temizle');
    }
}
