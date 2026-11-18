<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\CityGuide;
use App\Models\CityGuideSection;
use App\Models\CityGuideTopic;
use App\Models\Country;
use App\Models\Language;
use App\Models\User;
use App\Services\Translation\ModelTranslator;
use App\Support\HtmlSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickFactsAndTablesTest extends TestCase
{
    use RefreshDatabase;

    private function city(): City
    {
        $country = Country::create(['code' => 'GR', 'name' => 'Greece', 'slug' => 'greece', 'active' => true]);

        return City::create(['country_id' => $country->id, 'name' => 'Athens', 'slug' => 'athens', 'active' => true]);
    }

    public function test_tables_survive_sanitizer_without_unsafe_attributes(): void
    {
        $html = HtmlSanitizer::clean('<table class="x" onclick="a()"><thead><tr><th scope="evil" style="c">Area</th><th scope="col">Price</th></tr></thead>'
            . '<tbody><tr><td colspan="99">Plaka<script>alert(1)</script></td><td rowspan="2"><a href="javascript:alert(1)">x</a></td></tr></tbody></table>');

        $this->assertStringContainsString('<table><thead><tr><th>Area</th><th scope="col">Price</th></tr></thead>', $html);
        $this->assertStringContainsString('<td>Plaka</td>', $html);
        $this->assertStringContainsString('<td rowspan="2"><a href="#">x</a></td>', $html);
        $this->assertStringNotContainsString('script', $html);
        $this->assertStringNotContainsString('onclick', $html);
    }

    public function test_markdown_tables_become_real_tables(): void
    {
        $paragraphs = HtmlSanitizer::clean('<p>| Area | Best for |</p><p>|---|---|</p><p>| **Plaka** | First visit |</p><p>Next</p>');
        $this->assertSame('<table><thead><tr><th scope="col">Area</th><th scope="col">Best for</th></tr></thead><tbody><tr><td><strong>Plaka</strong></td><td>First visit</td></tr></tbody></table><p>Next</p>', $paragraphs);

        $breaks = HtmlSanitizer::clean('<p>| A | B |<br>| --- | --- |<br>| 1 | 2 |</p>');
        $this->assertStringContainsString('<td>1</td><td>2</td>', $breaks);

        // tek çizgili normal cümle tabloya dönmez
        $this->assertSame('<p>Price | not a table</p>', HtmlSanitizer::clean('<p>Price | not a table</p>'));
    }

    public function test_section_with_table_saves_and_table_text_changes_staleness(): void
    {
        $city = $this->city();
        $topic = CityGuideTopic::create(['key' => 'areas', 'name' => 'Semtler', 'icon' => 'map', 'sort_order' => 1, 'active' => true]);
        $section = CityGuideSection::create(['city_id' => $city->id, 'topic_id' => $topic->id, 'active' => true, 'sort_order' => 1]);
        $this->actingAs(User::factory()->admin()->create())->put(route('city-guides.sections.update', [$city, $section]), [
            'title' => 'Where to stay', 'content' => '<p>Intro</p><table><tbody><tr><td>Plaka</td><td>Central</td></tr></tbody></table>', 'active' => '1',
        ])->assertRedirect();

        $section->refresh();
        $this->assertStringContainsString('<td>Plaka</td><td>Central</td>', $section->getTranslation('content', 'en'));

        Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English']);
        Language::create(['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch']);
        $translator = app(ModelTranslator::class);
        $translator->markCurrent($section, 'de');
        $section->setTranslation('content', 'en', '<p>Intro</p><table><tbody><tr><td>Plaka</td><td>Quiet</td></tr></tbody></table>')->save();
        $this->assertNotSame('ok', $translator->status($section->fresh())['de']);
    }

    public function test_quick_facts_save_trim_and_clear(): void
    {
        $city = $this->city();
        $admin = User::factory()->admin()->create();
        CityGuide::create(['city_id' => $city->id, 'fact_currency' => ['en' => 'Euro', 'de' => 'Euro (alt)']]);

        $this->actingAs($admin)->get(route('city-guides.show', $city))->assertOk()->assertSee('fact_best_time', false);

        $this->put(route('city-guides.facts', $city), [
            'fact_best_time' => "  April–June\n and <b>September</b> ",
            'fact_currency' => '',
            'facts_checked_at' => now()->toDateString(),
        ])->assertRedirect(route('city-guides.show', $city) . '#kisa-bilgiler');

        $guide = CityGuide::where('city_id', $city->id)->first();
        $this->assertSame('April–June and September', $guide->getTranslation('fact_best_time', 'en'));
        $this->assertEmpty($guide->getTranslation('fact_currency', 'de', false)); // İngilizcesi silinince çevirisi de gider
        $this->assertSame(now()->toDateString(), $guide->facts_checked_at->toDateString());

        $this->put(route('city-guides.facts', $city), ['facts_checked_at' => now()->addDay()->toDateString()])->assertSessionHasErrors('facts_checked_at');
        $this->put(route('city-guides.facts', $city), ['fact_days' => str_repeat('a', 201)])->assertSessionHasErrors('fact_days');
    }
}
