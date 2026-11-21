<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\IndexNow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IndexNowTest extends TestCase
{
    use RefreshDatabase;

    private function sitemap(array $urls): string
    {
        $items = collect($urls)->map(fn ($lastmod, $loc) => '<url><loc>' . $loc . '</loc>' . ($lastmod ? '<lastmod>' . $lastmod . '</lastmod>' : '')
            . '<xhtml:link rel="alternate" hreflang="tr" href="' . $loc . '" /></url>')->implode('');

        return '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . $items . '</urlset>';
    }

    public function test_sends_new_and_changed_urls_only_once(): void
    {
        $site = config('site.url');
        $map = [
            $site . '/en' => null,
            $site . '/en/blog/rome' => '2026-11-20T10:00:00+00:00',
            $site . '/tr/activities/colosseum' => '2026-11-19T10:00:00+00:00',
            'https://other.example/en/x' => null, // başka alan adı gönderilmez
        ];
        Http::fake([
            $site . '/sitemap.xml' => function () use (&$map) {
                return Http::response($this->sitemap($map));
            },
            'api.indexnow.org/*' => Http::response('', 200),
        ]);

        $result = app(IndexNow::class)->sync();
        $this->assertSame(3, $result['sent']);
        Http::assertSent(fn ($r) => str_starts_with($r->url(), IndexNow::ENDPOINT) && $r['host'] === parse_url($site, PHP_URL_HOST)
            && $r['key'] === IndexNow::key() && $r['keyLocation'] === $site . '/' . IndexNow::key() . '.txt'
            && count($r['urlList']) === 3 && !in_array('https://other.example/en/x', $r['urlList'], true));

        // değişiklik yok: hiçbir şey gönderilmez
        $this->assertSame(0, app(IndexNow::class)->sync()['sent']);

        // bir blog güncellendi, bir aktivite eklendi: sadece o ikisi gider
        $map[$site . '/en/blog/rome'] = '2026-11-21T09:00:00+00:00';
        $map[$site . '/en/attractions/pantheon'] = '2026-11-21T09:00:00+00:00';
        $result = app(IndexNow::class)->sync();
        $this->assertSame(2, $result['sent']);
        Http::assertSent(fn ($r) => str_starts_with($r->url(), IndexNow::ENDPOINT) && $r['urlList'] === [$site . '/en/blog/rome', $site . '/en/attractions/pantheon']);
        $this->assertSame(4, DB::table('indexnow_urls')->count());
    }

    public function test_failed_submission_is_retried_next_time_and_shown_in_panel(): void
    {
        $site = config('site.url');
        $ok = false;
        Http::fake([
            $site . '/sitemap.xml' => Http::response($this->sitemap([$site . '/en' => null])),
            $site . '/' . IndexNow::key() . '.txt' => Http::response(IndexNow::key()),
            'api.indexnow.org/*' => function () use (&$ok) {
                return $ok ? Http::response('', 202) : Http::response('', 403);
            },
        ]);

        $result = app(IndexNow::class)->sync();
        $this->assertSame(0, $result['sent']);
        $this->assertStringContainsString('403', $result['error']);
        $this->assertSame(0, DB::table('indexnow_urls')->count()); // gönderilemeyen kaydedilmez: sonra yeniden denenir

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('indexnow.index'))->assertOk()->assertSee('403')->assertSee(__('Doğru ✓'));

        $ok = true;
        $this->post(route('indexnow.sync'))->assertSessionHas('success');
        $this->assertSame(1, DB::table('indexnow_urls')->count());

        // hepsini yeniden gönder
        $this->post(route('indexnow.resend'))->assertSessionHas('success');
        $this->assertSame(3, Http::recorded(fn ($r) => str_starts_with($r->url(), IndexNow::ENDPOINT))->count()); // 403, 202, 202
    }

    public function test_scheduled_command_runs_sync(): void
    {
        Http::fake([
            config('site.url') . '/sitemap.xml' => Http::response($this->sitemap([config('site.url') . '/en' => null])),
            'api.indexnow.org/*' => Http::response('', 200),
        ]);

        $this->artisan('indexnow:sync')->assertSuccessful();
        $this->assertSame(1, DB::table('indexnow_urls')->count());
    }

    public function test_panel_lists_reported_pages_with_search_filters_and_pages(): void
    {
        $site = config('site.url');
        $rows = [];
        foreach (range(1, 60) as $i) {
            $rows[] = ['url_hash' => sha1("a$i"), 'url' => "$site/en/activities/tour-$i", 'lastmod' => '', 'submitted_at' => now()->subMinutes($i)];
        }
        $rows[] = ['url_hash' => sha1('rome'), 'url' => "$site/tr/blog/rome-guide", 'lastmod' => '', 'submitted_at' => now()];
        $rows[] = ['url_hash' => sha1('home'), 'url' => "$site/tr", 'lastmod' => '', 'submitted_at' => now()];
        DB::table('indexnow_urls')->insert($rows);
        $admin = User::factory()->admin()->create();
        Http::fake();

        $this->actingAs($admin)->get(route('indexnow.index'))->assertOk()->assertSee('rome-guide')->assertSee('?page=2', false);
        $this->get(route('indexnow.index', ['search' => 'rome']))->assertSee('rome-guide')->assertDontSee('tour-1"', false)->assertDontSee('tour-12');
        $this->get(route('indexnow.index', ['lang' => 'tr']))->assertSee('rome-guide')->assertSee(\Illuminate\Support\Str::after("$site/tr<", '://'), false)->assertDontSee('tour-12');
        $this->get(route('indexnow.index', ['type' => 'blog']))->assertSee('rome-guide')->assertDontSee('tour-12');
        $this->get(route('indexnow.index', ['type' => 'other']))->assertDontSee('rome-guide')->assertDontSee('tour-12');
        $this->get(route('indexnow.index', ['page' => 2]))->assertSee('tour-59');
    }
}
