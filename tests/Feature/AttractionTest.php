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

    public function test_changing_slug_redirects_old_address_in_every_language(): void
    {
        \App\Models\Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'sort_order' => 0, 'active' => true]);
        \App\Models\Language::create(['code' => 'tr', 'name' => 'Turkish', 'native_name' => 'Türkçe', 'sort_order' => 1, 'active' => true]);
        $country = Country::create(['code' => 'GR', 'name' => 'Greece', 'slug' => 'greece', 'active' => true]);
        $city = City::create(['country_id' => $country->id, 'name' => 'Athens', 'slug' => 'athens', 'active' => true]);
        $place = Attraction::create(['city_id' => $city->id, 'slug' => 'acropolis', 'name' => ['en' => 'Acropolis of Athens']]);

        $place->update(['slug' => 'acropolis-of-athens']);

        foreach (['en', 'tr'] as $locale) {
            $this->assertDatabaseHas('redirects', ['from_path' => "/{$locale}/attractions/acropolis", 'to_path' => "/{$locale}/attractions/acropolis-of-athens"]);
        }
    }

    public function test_list_toggle_works_without_reload_and_form_uses_activity_select(): void
    {
        $country = Country::create(['code' => 'TR', 'name' => 'Turkey', 'slug' => 'turkey', 'active' => true]);
        $city = City::create(['country_id' => $country->id, 'name' => 'Istanbul', 'slug' => 'istanbul', 'active' => true]);
        $act = Activity::create(['city_id' => $city->id, 'name' => ['en' => 'Topkapi ticket'], 'slug' => 'topkapi-ticket', 'type' => 'product']);
        $place = Attraction::create(['city_id' => $city->id, 'slug' => 'topkapi', 'name' => ['en' => 'Topkapi Palace'], 'active' => false]);
        $place->activities()->sync([$act->id]);
        $admin = User::factory()->admin()->create();

        // kapalıyken "Sitede aç" bağlantı değil (pasif)
        $this->actingAs($admin)->get(route('attractions.index'))->assertOk()
            ->assertSee(route('attractions.toggle', $place), false)
            ->assertSee('rel="noopener" class="relative z-10 text-sm font-semibold text-slate-500 hover:text-brand-900 hidden"', false)
            ->assertSee('cursor-not-allowed select-none text-sm font-semibold text-slate-300"', false);

        $this->actingAs($admin)->patchJson(route('attractions.toggle', $place))
            ->assertOk()->assertJson(['active' => true]);
        $this->assertTrue($place->fresh()->active);

        $this->actingAs($admin)->get(route('attractions.index'))
            ->assertSee('rel="noopener" class="relative z-10 text-sm font-semibold text-slate-500 hover:text-brand-900"', false)
            ->assertSee('text-slate-300 hidden"', false);

        // formda "Aktiviteler" adımı, seçili aktivite etiket olarak gelir
        $this->actingAs($admin)->get(route('attractions.edit', $place))->assertOk()
            ->assertSee('Aktiviteler')->assertDontSee('Biletler')
            ->assertSee('data-activity-select', false)
            ->assertSee('name="activity_ids[]" value="' . $act->id . '"', false);
    }

    public function test_list_groups_by_city_filters_and_flags_missing_images(): void
    {
        $country = Country::create(['code' => 'CZ', 'name' => 'Czechia', 'slug' => 'czechia', 'active' => true]);
        $prague = City::create(['country_id' => $country->id, 'name' => 'Prague', 'slug' => 'prague', 'active' => true]);
        $brno = City::create(['country_id' => $country->id, 'name' => 'Brno', 'slug' => 'brno', 'active' => true]);
        $castle = Attraction::create(['city_id' => $prague->id, 'slug' => 'prague-castle', 'name' => ['en' => 'Prague Castle'], 'active' => true]);
        $spilberk = Attraction::create(['city_id' => $brno->id, 'slug' => 'spilberk', 'name' => ['en' => 'Spilberk Castle'], 'active' => false]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('attractions.index'))->assertOk()
            ->assertSee('Prague')->assertSee('Brno')
            ->assertSee('2 yerin resmi yok', false)
            ->assertSee('Resmi eksikleri göster')
            // menü şeklinde: varsayılan ilk şehir (ada göre Brno) açılır, diğeri menüde bağlantı
            ->assertSee(route('attractions.toggle', $spilberk), false)->assertDontSee(route('attractions.toggle', $castle), false)
            ->assertSee(route('attractions.index', ['city' => $prague->id]), false);

        $this->actingAs($admin)->get(route('attractions.index', ['city' => $prague->id]))
            ->assertSee('Prague Castle')->assertDontSee('Spilberk Castle');
        $this->actingAs($admin)->get(route('attractions.index', ['status' => 'passive']))
            ->assertSee('Spilberk Castle')->assertDontSee('Prague Castle');
        $this->actingAs($admin)->get(route('attractions.index', ['search' => 'spil']))
            ->assertSee('Spilberk Castle')->assertDontSee('Prague Castle');
    }
}
