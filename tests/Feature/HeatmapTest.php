<?php

namespace Tests\Feature;

use App\Models\PageClick;
use App\Models\User;
use App\Models\Visit;
use App\Services\Engagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeatmapTest extends TestCase
{
    use RefreshDatabase;

    private function visit(string $path, int $duration, int $scroll, string $device = 'desktop', ?string $who = null): Visit
    {
        return Visit::create(['visitor_id' => $who ?? str()->random(32), 'session_id' => str()->random(32), 'path' => $path, 'page_type' => 'activity',
            'device' => $device, 'duration' => $duration, 'scroll' => $scroll, 'viewport_h' => 800, 'created_at' => now()]);
    }

    private function click(Visit $visit, array $data): void
    {
        PageClick::create(['visit_id' => $visit->id, 'path' => $visit->path, 'path_hash' => sha1($visit->path), 'device' => $visit->device,
            'x' => 700, 'y' => 400, 'vw' => 1440, 'ph' => 5000, 'created_at' => now(), ...$data]);
    }

    public function test_heatmap_page_shows_metrics_clicks_and_advice(): void
    {
        $path = '/en/activities/colosseum';
        foreach (range(1, 8) as $i) {
            $visit = $this->visit($path, $i <= 6 ? 4 : 90, 30);
            $this->click($visit, ['label' => 'Book now', 'href' => 'https://partner.example/x', 'y' => 4200]);
        }
        $this->click($visit, ['label' => 'Price', 'rage' => true]);
        $this->click($visit, ['label' => 'Price', 'rage' => true]);
        $this->visit('/en/other', 10, 50, 'mobile');

        $this->actingAs(User::factory()->create())->get(route('analytics.heatmap', ['page' => $path]))
            ->assertOk()
            ->assertSee('Book now')->assertSee('Sinirli tıklama var')->assertSee('Gelenlerin çoğu hemen çıkıyor')
            ->assertSee('Rezervasyon düğmesi çok aşağıda')->assertSee('heatmap-data', false)->assertSee('?ts-heatmap=1', false);

        // telefon görünümünde bilgisayar tıklamaları yok
        $this->actingAs(User::factory()->create())->get(route('analytics.heatmap', ['page' => $path, 'device' => 'mobile']))
            ->assertOk()->assertSee('Veri toplanıyor');
    }

    public function test_page_report_flags_and_visitor_durations(): void
    {
        foreach (range(1, 6) as $i) {
            $this->visit('/en/fast-exit', 3, 20, 'desktop', 'same-visitor');
        }
        $pages = (new Engagement(now()->subDay()->toImmutable(), now()->endOfDay()->toImmutable()))->pages();
        $row = $pages->firstWhere('path', '/en/fast-exit');
        $this->assertSame(100, $row->quick);
        $this->assertContains('Hemen çıkıyorlar', array_column($row->flags, 0));

        $this->assertSame('1 dk 15 sn', Engagement::duration(75));
        $this->actingAs(User::factory()->create())->get(route('analytics.visitor', 'same-visitor'))->assertOk()->assertSee('3 sn')->assertSee('↓ %20');
    }

    public function test_visitor_list_shows_measured_estimated_or_unknown_duration(): void
    {
        $this->visit('/en/a', 125, 50, 'desktop', 'measured-visitor');
        // ölçümden önceki ziyaret: iki sayfa, arada 3 dakika
        Visit::create(['visitor_id' => 'old-visitor', 'session_id' => 's1', 'path' => '/en/x', 'device' => 'desktop', 'created_at' => now()->subMinutes(3)]);
        Visit::create(['visitor_id' => 'old-visitor', 'session_id' => 's1', 'path' => '/en/y', 'device' => 'desktop', 'created_at' => now()]);
        Visit::create(['visitor_id' => 'single-visitor', 'session_id' => 's2', 'path' => '/en/z', 'device' => 'mobile', 'created_at' => now()]);

        $rows = \App\Services\Analytics::visitors()->keyBy('who');
        $this->assertSame([125, false], [$rows['measured-visitor']->duration, $rows['measured-visitor']->estimated]);
        $this->assertSame([180, true], [$rows['old-visitor']->duration, $rows['old-visitor']->estimated]);
        $this->assertNull($rows['single-visitor']->duration);

        $this->actingAs(User::factory()->create())->get(route('analytics.live'))->assertOk()
            ->assertSee('2 dk 5 sn')->assertSee('~3 dk')->assertSee('bilinmiyor')->assertSee('device=mobile', false);
    }
}
