<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Attraction;
use App\Models\City;
use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_update_links_tickets_and_faqs(): void
    {
        $country = Country::create(['code' => 'TR', 'name' => 'Turkey', 'slug' => 'turkey', 'active' => true]);
        $city = City::create(['country_id' => $country->id, 'name' => 'Istanbul', 'slug' => 'istanbul', 'active' => true]);
        $a = Activity::create(['city_id' => $city->id, 'name' => ['en' => 'Topkapi ticket'], 'slug' => 'topkapi-ticket', 'type' => 'product']);
        $b = Activity::create(['city_id' => $city->id, 'name' => ['en' => 'Combo'], 'slug' => 'combo', 'type' => 'package']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('attractions.create'))->assertOk();
        $this->post(route('attractions.store'), [
            'city_id' => $city->id, 'name' => 'Topkapi Palace', 'summary' => 'Palace of the sultans.',
            'opening_hours' => 'Wed–Mon 09:00–18:00', 'good_to_know' => "- Closed on Tuesdays\n\n• Security check",
            'official_url' => 'https://muze.gov.tr', 'activity_ids' => [$a->id, $b->id], 'active' => '1',
            'faqs' => [['question' => 'Is the Harem included?', 'answer' => 'It needs a separate ticket.']],
        ])->assertSessionHasNoErrors();

        $attraction = Attraction::firstOrFail();
        $this->assertSame('topkapi-palace', $attraction->slug);
        $this->assertSame("Closed on Tuesdays\nSecurity check", $attraction->getTranslation('good_to_know', 'en'));
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $attraction->activity_ids);
        $this->assertCount(1, $attraction->faqs);
        $this->assertTrue($attraction->active);

        $this->get(route('attractions.edit', $attraction))->assertOk()->assertSee('Topkapi Palace');
        $this->get(route('attractions.index'))->assertOk()->assertSee('Topkapi Palace');

        // MCP gibi: activity_ids metin olarak
        $attraction->update(['activity_ids' => (string) $a->id]);
        $this->assertSame([$a->id], $attraction->fresh()->activity_ids);

        $this->put(route('attractions.update', $attraction), [
            'city_id' => $city->id, 'name' => 'Topkapi Palace', 'official_url' => 'not-a-url',
        ])->assertSessionHasErrors('official_url');
    }
}
