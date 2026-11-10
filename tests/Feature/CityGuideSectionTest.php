<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\CityGuideSection;
use App\Models\CityGuideTopic;
use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CityGuideSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_save_stays_on_page_and_shows_translations(): void
    {
        Storage::fake('media');
        $country = Country::create(['code' => 'TR', 'name' => 'Turkey', 'slug' => 'turkey', 'active' => true]);
        $city = City::create(['country_id' => $country->id, 'name' => 'Istanbul', 'slug' => 'istanbul', 'active' => true]);
        $topic = CityGuideTopic::create(['key' => 'transport', 'name' => 'Şehir içi ulaşım', 'icon' => 'bus', 'sort_order' => 1, 'active' => true]);
        $section = CityGuideSection::create(['city_id' => $city->id, 'topic_id' => $topic->id, 'active' => false, 'sort_order' => 1]);
        $admin = User::factory()->admin()->create();

        $edit = route('city-guides.sections.edit', [$city, $section]);
        $this->actingAs($admin)->get($edit)->assertOk()->assertDontSee('data-live="translations-city-guide-section-' . $section->id . '"', false);

        $this->put(route('city-guides.sections.update', [$city, $section]), [
            'title' => 'Getting around Istanbul', 'content' => '<p>Metro and ferries.</p>', 'active' => '1',
        ])->assertRedirect($edit);

        $this->get($edit)->assertOk()->assertSee('data-live="translations-city-guide-section-' . $section->id . '"', false);
    }
}
