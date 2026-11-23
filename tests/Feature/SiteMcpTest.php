<?php

namespace Tests\Feature;

use App\Mcp\Site;
use App\Mcp\Site\SiteServer;
use App\Models\Activity;
use App\Models\Blog;
use App\Models\City;
use App\Models\Country;
use App\Models\Faq;
use App\Models\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteMcpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['site.url' => 'https://tripspoiler.com']);
        foreach ([['en', 'English'], ['de', 'German']] as $i => [$code, $name]) {
            Language::create(['code' => $code, 'name' => $name, 'native_name' => $name, 'sort_order' => $i, 'active' => true]);
        }
        $italy = Country::create(['code' => 'IT', 'name' => 'Italy', 'slug' => 'italy', 'active' => true]);
        $rome = City::create(['country_id' => $italy->id, 'name' => 'Rome', 'slug' => 'rome', 'active' => true]);
        $colosseum = Activity::create(['city_id' => $rome->id, 'slug' => 'colosseum-tickets', 'type' => 'product', 'active' => true, 'duration' => '3 hours',
            'name' => ['en' => 'Colosseum Skip-the-Line Tickets', 'de' => 'Kolosseum Tickets'], 'description' => ['en' => '<p>Enter <strong>fast</strong>.</p>'],
            'highlights' => ['en' => '<ul><li>Arena floor</li></ul>']]);
        Faq::create(['activity_id' => $colosseum->id, 'question' => ['en' => 'Is it open on Sunday?'], 'answer' => ['en' => 'Yes.'], 'active' => true]);
        Activity::create(['city_id' => $rome->id, 'slug' => 'secret-draft', 'type' => 'product', 'active' => false, 'name' => ['en' => 'Colosseum draft']]);
        Blog::create(['title' => ['en' => '3 days in Rome'], 'slug' => '3-days-in-rome', 'excerpt' => ['en' => 'A plan.'], 'content' => ['en' => '<p>x</p>'], 'active' => true, 'published_at' => now()->subDay()]);
        Blog::create(['title' => ['en' => 'Rome draft post'], 'slug' => 'rome-draft', 'content' => ['en' => '<p>x</p>'], 'active' => false]);
    }

    public function test_public_tools_show_only_published_content_with_site_links(): void
    {
        SiteServer::tool(Site\SearchActivities::class, ['query' => 'Colosseum'])->assertOk()
            ->assertSee('Colosseum Skip-the-Line Tickets')->assertSee('https://tripspoiler.com/en/activities/colosseum-tickets')
            ->assertDontSee('secret-draft')->assertDontSee('/yonetim');

        SiteServer::tool(Site\SearchActivities::class, ['city' => 'rome', 'language' => 'de'])->assertOk()
            ->assertSee('Kolosseum Tickets')->assertSee('/de/activities/kolosseum-tickets');

        SiteServer::tool(Site\GetActivity::class, ['slug' => 'colosseum-tickets'])->assertOk()
            ->assertSee('Enter fast.')->assertSee('Arena floor')->assertSee('Is it open on Sunday?');
        SiteServer::tool(Site\GetActivity::class, ['slug' => 'secret-draft'])->assertHasErrors();

        SiteServer::tool(Site\SearchGuides::class, ['query' => 'Rome'])->assertOk()
            ->assertSee('3 days in Rome')->assertSee('/en/blog/3-days-in-rome')->assertDontSee('Rome draft post');

        SiteServer::tool(Site\ListCities::class)->assertOk()->assertSee('Rome')->assertSee('https://tripspoiler.com/en/cities/rome');
    }

    public function test_public_endpoint_needs_no_login(): void
    {
        $this->postJson('/mcp/site', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'], ['Accept' => 'application/json, text/event-stream'])
            ->assertOk()->assertSee('search_activities')->assertDontSee('save');
    }
}
