<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\City;
use App\Models\CityGuide;
use App\Models\CityGuideSection;
use App\Models\CityGuideTopic;
use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private City $city;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('media');
        $this->admin = User::factory()->admin()->create();
        $country = Country::create(['code' => 'TR', 'name' => 'Turkey', 'slug' => 'turkey', 'active' => true]);
        $this->city = City::create(['country_id' => $country->id, 'name' => 'Istanbul', 'slug' => 'istanbul', 'active' => true]);
    }

    public function test_blog_paste_creates_draft_with_seo_tags_and_faqs(): void
    {
        $html = <<<'HTML'
```html
<title>Is the Basilica Cistern Worth It?</title>
<meta name="excerpt" content="An honest local guide.">
<meta name="seo-title" content="Basilica Cistern: Is It Worth It?">
<meta name="seo-description" content="Everything you need to know before visiting.">
<meta name="tags" content="Istanbul, Cistern, istanbul">
<article>
<h1>Is the Basilica Cistern Worth It?</h1>
<p>Short answer: <strong>yes</strong>.</p>
<h2>History</h2>
<p>Built in 532.</p>
<img src="https://example.com/x.jpg">
<script>alert(1)</script>
</article>
<section class="faq">
<h3>How long does a visit take?</h3>
<p>About 30 minutes.</p>
<h3>Can I take photos?</h3>
<p>Yes.</p>
</section>
```
HTML;

        $this->actingAs($this->admin)->get(route('blogs.import'))->assertOk()->assertSee('Talimatı kopyala');
        $response = $this->post(route('blogs.import.store'), ['html' => $html, 'city_id' => $this->city->id]);

        $blog = Blog::firstOrFail();
        $response->assertRedirect(route('blogs.edit', $blog));
        $this->assertSame('Is the Basilica Cistern Worth It?', $blog->title);
        $this->assertSame('is-the-basilica-cistern-worth-it', $blog->slug);
        $this->assertFalse($blog->active);
        $this->assertSame('An honest local guide.', $blog->excerpt);
        $this->assertSame('Basilica Cistern: Is It Worth It?', $blog->meta_title);
        $this->assertSame(['Istanbul', 'Cistern'], $blog->tags);
        $this->assertStringContainsString('<h2>History</h2>', $blog->content);
        $this->assertStringNotContainsString('<h1>', $blog->content);
        $this->assertStringNotContainsString('<img', $blog->content);
        $this->assertStringNotContainsString('script', $blog->content);
        $this->assertStringNotContainsString('How long', $blog->content);
        $this->assertSame([$this->city->id], $blog->cities->pluck('id')->all());
        $this->assertSame(['How long does a visit take?', 'Can I take photos?'], $blog->faqs->pluck('question')->all());

        // aynı yazı ikinci kez: eklenmez
        $this->post(route('blogs.import.store'), ['html' => $html])->assertSessionHas('success', fn ($m) => str_contains($m, 'atlananlar'));
        $this->assertSame(1, Blog::count());
    }

    public function test_blog_paste_without_title_shows_error(): void
    {
        $this->actingAs($this->admin)->from(route('blogs.import'))
            ->post(route('blogs.import.store'), ['html' => '<p>just text</p>'])
            ->assertRedirect(route('blogs.import'))->assertSessionHasErrors('html');
        $this->assertSame(0, Blog::count());
    }

    public function test_city_guide_paste_fills_intro_and_sections(): void
    {
        $sights = CityGuideTopic::firstOrCreate(['key' => 'sights'], ['name' => ['en' => 'Things to see'], 'icon' => 'pin', 'sort_order' => 1, 'active' => true]);
        CityGuideTopic::firstOrCreate(['key' => 'food'], ['name' => ['en' => 'Food & drink'], 'icon' => 'food', 'sort_order' => 2, 'active' => true]);

        $this->actingAs($this->admin)->get(route('city-guides.import', ['city' => $this->city->id]))->assertOk()
            ->assertSee('data-topic=&quot;food&quot;', false)->assertSee('ŞEHİRLER: Istanbul');

        // şehir sayfasından: <guide> olmadan, seçili şehre
        $this->post(route('city-guides.import.store'), ['city_id' => $this->city->id, 'html' => <<<'HTML'
<header><h1>Istanbul travel guide</h1><p>Two continents.</p><p>One city.</p></header>
<section data-topic="sights"><h2>Best things to see in Istanbul</h2><p>Hagia Sophia.</p></section>
<section data-topic="nightlife"><h2>Nightlife</h2><p>x</p></section>
HTML])->assertRedirect(route('city-guides.show', $this->city))->assertSessionHas('success', fn ($m) => str_contains($m, 'nightlife'));

        $guide = CityGuide::where('city_id', $this->city->id)->firstOrFail();
        $this->assertSame('Istanbul travel guide', $guide->title);
        $this->assertSame("Two continents.\n\nOne city.", $guide->description);

        $section = CityGuideSection::where('topic_id', $sights->id)->firstOrFail();
        $this->assertSame('Best things to see in Istanbul', $section->title);
        $this->assertSame('<p>Hagia Sophia.</p>', $section->content);
        $this->assertTrue($section->active);
        $this->assertFalse(CityGuideSection::whereHas('topic', fn ($q) => $q->where('key', 'food'))->firstOrFail()->active);
    }

    public function test_missing_images_show_in_menu_and_lists(): void
    {
        $topic = CityGuideTopic::firstOrCreate(['key' => 'sights'], ['name' => ['en' => 'Things to see'], 'icon' => 'pin', 'sort_order' => 1, 'active' => true]);
        CityGuideSection::create(['city_id' => $this->city->id, 'topic_id' => $topic->id, 'title' => ['en' => 'See'], 'content' => ['en' => '<p>x</p>'], 'active' => true, 'sort_order' => 1]);
        Blog::create(['slug' => 'a', 'title' => ['en' => 'A'], 'active' => false]);
        Blog::create(['slug' => 'b', 'title' => ['en' => 'B'], 'active' => true, 'cover_path' => 'covers/b.webp']);

        $this->actingAs($this->admin)->get(route('blogs.index'))->assertOk()
            ->assertSee('1 kapak resmi eksik yazı')
            ->assertSee('1 kapak resmi eksik alan')
            ->assertSee('Resim eksik');
        $this->get(route('city-guides.index'))->assertOk()->assertSee('1 alanda resim eksik');
        $this->get(route('city-guides.show', $this->city))->assertOk()->assertSee('Resim eksik');
    }

    public function test_several_blog_posts_in_one_paste_and_duplicates_skipped(): void
    {
        Blog::create(['slug' => 'old', 'title' => ['en' => 'Istanbul in 2 Days'], 'active' => true]);
        $post = fn ($t) => "<post><title>{$t}</title><meta name=\"tags\" content=\"a\"><article><p>Body of {$t}.</p></article><section class=\"faq\"><h3>Q?</h3><p>A.</p></section></post>";
        $html = $post('First Post') . $post('Second Post') . $post('istanbul in 2 days');

        $this->actingAs($this->admin)->get(route('blogs.import'))->assertOk()->assertSee('KAÇ YAZI')->assertSee('- Istanbul in 2 Days');
        $this->post(route('blogs.import.store'), ['html' => $html])
            ->assertRedirect(route('blogs.index', ['status' => 'passive', 'cover' => 'no']))
            ->assertSessionHas('success', fn ($m) => str_contains($m, '2 yazı') && str_contains($m, 'istanbul in 2 days'));

        $first = Blog::where('slug', 'first-post')->firstOrFail();
        $this->assertSame('<p>Body of First Post.</p>', $first->content);
        $this->assertSame(['Q?'], $first->faqs->pluck('question')->all());
        $this->assertSame('Second Post', Blog::where('slug', 'second-post')->firstOrFail()->title);
        $this->assertSame(now()->toDateString(), $first->published_at->toDateString());
        $this->assertSame(now()->subDays(3)->toDateString(), Blog::where('slug', 'second-post')->first()->published_at->toDateString());
        $this->assertSame(3, Blog::count());

        // aynısı tekrar: hiçbiri eklenmez
        $this->post(route('blogs.import.store'), ['html' => $html]);
        $this->assertSame(3, Blog::count());
    }

    public function test_several_city_guides_in_one_paste(): void
    {
        CityGuideTopic::firstOrCreate(['key' => 'sights'], ['name' => ['en' => 'Things to see'], 'icon' => 'pin', 'sort_order' => 1, 'active' => true]);
        $antalya = City::create(['country_id' => $this->city->country_id, 'name' => 'Antalya', 'slug' => 'antalya', 'active' => true]);

        $this->actingAs($this->admin)->get(route('city-guides.import'))->assertOk()->assertSee('Antalya, Istanbul');
        $this->post(route('city-guides.import.store'), ['html' => <<<'HTML'
```html
<guide data-city="İstanbul"><header><h1>Istanbul guide</h1><p>Hi.</p></header><section data-topic="sights"><h2>See Istanbul</h2><p>A.</p></section></guide>
```
```html
<guide data-city="ANTALYA"><header><h1>Antalya guide</h1></header><section data-topic="sights"><h2>See Antalya</h2><p>B.</p></section></guide>
<guide data-city="Paris"><section data-topic="sights"><h2>See Paris</h2><p>C.</p></section></guide>
```
HTML])->assertRedirect(route('city-guides.index'))->assertSessionHas('success', fn ($m) => str_contains($m, 'Paris'));

        $this->assertSame('Istanbul guide', CityGuide::where('city_id', $this->city->id)->first()->title);
        $this->assertSame('Antalya guide', CityGuide::where('city_id', $antalya->id)->first()->title);
        $this->assertSame('See Antalya', CityGuideSection::where('city_id', $antalya->id)->whereHas('topic', fn ($q) => $q->where('key', 'sights'))->first()->title);
    }
}
