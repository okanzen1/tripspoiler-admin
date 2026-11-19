<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use App\Support\PageSections;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Şehir sayfasındaki Kısa bilgiler ve Gezilecek yerler panelde sıralanabilir / kapatılabilir */
class CityBlocksSectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_city_blocks_start_on_top_and_can_be_moved_and_hidden(): void
    {
        // eski kayıt (bloklar yokken kaydedilmiş): bloklar yine en üstte ve açık başlar
        Setting::put('page_sections_cities', json_encode(['order' => ['weather', 'reviews'], 'on' => ['weather', 'reviews']]));
        $config = PageSections::config('cities');
        $this->assertSame(['city_facts', 'city_places'], array_slice($config['order'], 0, 2));
        $this->assertContains('city_facts', $config['on']);
        $this->assertContains('city_places', $config['on']);

        // panelde aşağı taşınıp biri kapatılınca öyle kalır
        PageSections::save('cities', ['weather', 'city_places', 'reviews', 'city_facts'], ['weather', 'reviews', 'city_facts']);
        $config = PageSections::config('cities');
        $this->assertSame(['weather', 'city_places', 'reviews', 'city_facts'], array_slice($config['order'], 0, 4));
        $this->assertNotContains('city_places', $config['on']);

        $page = Page::firstOrCreate(['key' => 'cities'], ['name' => 'Şehirler', 'path' => '/cities']);
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('pages.sections', $page))->assertOk()
            ->assertSee('Kısaca şehir (kısa bilgiler)')
            ->assertSee('Gezilecek yerler ve pass karşılaştırması');
    }
}
