<?php

namespace Tests\Feature;

use App\Mcp\Servers\TripSpoilerServer;
use App\Mcp\Tools;
use App\Models\Activity;
use App\Models\City;
use App\Models\Country;
use App\Models\Language;
use App\Models\Partner;
use App\Models\Review;
use App\Models\User;
use App\Services\Translation\GoogleTranslator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Fakes\FakeGoogleTranslator;
use Tests\TestCase;

class AssistantTest extends TestCase
{
    use RefreshDatabase;

    private City $istanbul;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('media');
        $turkey = Country::create(['code' => 'TR', 'name' => 'Türkiye', 'slug' => 'turkiye', 'active' => true]);
        $this->istanbul = City::create(['country_id' => $turkey->id, 'name' => 'Istanbul', 'slug' => 'istanbul', 'active' => true, 'population' => 15000000]);
        City::create(['country_id' => $turkey->id, 'name' => 'İzmir', 'slug' => 'izmir', 'population' => 3000000]);
        Partner::create(['name' => 'Megapass', 'slug' => 'megapass', 'website' => 'https://megapass.com', 'active' => true]);
    }

    public function test_reads_a_product_page(): void
    {
        Http::fake(['megapass.com/*' => Http::response('<html><head><title>Istanbul Pass</title><meta name="description" content="All in one"><script type="application/ld+json">{"@type":"Product","name":"Istanbul Pass"}</script></head><body><nav>menu</nav><h1>Istanbul Pass</h1><p>Hagia Sophia included.</p><img src="https://cdn.megapass.com/p1.jpg"></body></html>')]);

        TripSpoilerServer::tool(Tools\ReadWebPage::class, ['url' => 'https://megapass.com/istanbul-pass'])
            ->assertOk()
            ->assertSee(['Istanbul Pass', 'All in one', 'Hagia Sophia included.', 'p1.jpg'])
            ->assertDontSee('menu');
    }

    public function test_creates_an_activity_as_draft_with_images_and_faqs(): void
    {
        $jpeg = UploadedFile::fake()->image('a.jpg', 1800, 1200)->getContent();
        Http::fake(['cdn.megapass.com/*' => Http::response($jpeg, 200, ['Content-Type' => 'image/jpeg'])]);

        TripSpoilerServer::tool(Tools\SaveActivity::class, [
            'name' => 'Izmir Food Tour',
            'city' => 'Izmir',
            'partner' => 'Megapass',
            'type' => 'product',
            'affiliate_url' => 'https://megapass.com/izmir-food',
            'description_html' => '<h2>Highlights</h2><p>Taste <strong>boyoz</strong></p><script>x()</script>',
            'image_urls' => ['https://cdn.megapass.com/1.jpg', 'https://cdn.megapass.com/2.jpg'],
            'faqs' => [['question' => 'Is it vegetarian friendly?', 'answer' => 'Yes.']],
        ])->assertOk()->assertSee(['created', 'izmir-food-tour']);

        $activity = Activity::where('slug', 'izmir-food-tour')->first();
        $this->assertFalse($activity->active); // taslak
        $this->assertSame('<h2>Highlights</h2><p>Taste <strong>boyoz</strong></p>', $activity->description);
        $this->assertSame('Megapass', $activity->partner->name);
        $this->assertCount(2, $activity->images);
        $this->assertSame(1600, $activity->images->first()->width);
        $this->assertCount(1, $activity->faqs);
        $this->assertTrue($activity->city->active); // şehir açıldı

        // Güncelleme: sadece gönderilen alan değişir
        TripSpoilerServer::tool(Tools\SaveActivity::class, ['activity' => 'izmir-food-tour', 'active' => true, 'popular' => true])->assertOk();
        $activity->refresh();
        $this->assertTrue($activity->active && $activity->is_popular);
        $this->assertSame('Izmir Food Tour', $activity->getTranslation('name', 'en'));
    }

    public function test_unknown_city_gives_a_helpful_error(): void
    {
        TripSpoilerServer::tool(Tools\SaveActivity::class, ['name' => 'X', 'city' => 'Atlantis'])
            ->assertHasErrors(['City "Atlantis" not found. Use list-cities to search.']);
        $this->assertSame(0, Activity::count());
    }

    public function test_review_city_user_faq_and_translate(): void
    {
        $activity = Activity::create(['city_id' => $this->istanbul->id, 'name' => ['en' => 'Hagia Sophia'], 'slug' => 'hagia-sophia', 'type' => 'product']);

        TripSpoilerServer::tool(Tools\AddReview::class, ['name' => 'Emily Carter', 'rating' => 5, 'comment' => 'Great!', 'activity' => 'hagia-sophia'])->assertOk();
        $this->assertTrue(Review::first()->approved);
        $this->assertTrue(Review::first()->activity->is($activity));

        TripSpoilerServer::tool(Tools\SetCityStatus::class, ['city' => 'İzmir', 'active' => true])->assertOk();
        $this->assertTrue(City::where('slug', 'izmir')->value('active'));

        TripSpoilerServer::tool(Tools\CreateUser::class, ['first_name' => 'Ayşe', 'last_name' => 'Yılmaz', 'email' => 'ayse@tripspoiler.com'])->assertOk()->assertSee('editor');
        $this->assertTrue(User::where('email', 'ayse@tripspoiler.com')->exists());
        TripSpoilerServer::tool(Tools\CreateUser::class, ['first_name' => 'A', 'last_name' => 'B', 'email' => 'ayse@tripspoiler.com'])->assertHasErrors();

        TripSpoilerServer::tool(Tools\AddFaq::class, ['question' => 'Open on Mondays?', 'answer' => 'Yes.', 'activity' => 'Hagia Sophia'])->assertOk();
        $this->assertSame(1, $activity->faqs()->count());

        $this->app->instance(GoogleTranslator::class, new FakeGoogleTranslator());
        Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English']);
        Language::create(['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch']);
        TripSpoilerServer::tool(Tools\Translate::class, ['type' => 'activity', 'id' => 'hagia-sophia'])->assertOk()->assertSee('de');
        $this->assertSame('[de] Hagia Sophia', $activity->fresh()->getTranslation('name', 'de'));
    }
}
