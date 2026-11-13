<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\AffiliateClick;
use App\Models\City;
use App\Models\Country;
use App\Models\Partner;
use App\Models\User;
use App\Support\HtmlSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;
    private City $city;
    private Partner $partner;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('media');
        $this->editor = User::factory()->create(); // aktiviteleri editör de yönetir
        $country = Country::create(['code' => 'TR', 'name' => 'Türkiye', 'slug' => 'turkiye', 'active' => true]);
        $this->city = City::create(['country_id' => $country->id, 'name' => 'Istanbul', 'slug' => 'istanbul', 'active' => true]);
        $this->partner = Partner::create(['name' => 'GetYourGuide', 'slug' => 'getyourguide', 'active' => true]);
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Hagia Sophia Skip the Line',
            'type' => 'product',
            'city_id' => $this->city->id,
            'partner_id' => $this->partner->id,
            'affiliate_url' => 'https://www.getyourguide.com/hagia-sophia',
            'duration' => '2 hours',
            'audio_guide' => '1',
            'description' => '<h2>About</h2><p onclick="alert(1)">Great <strong>tour</strong></p><script>alert(1)</script>',
            'meta_title' => 'Hagia Sophia Tickets',
            'active' => '1',
            'is_popular' => '1',
        ];
    }

    public function test_editor_can_create_activity_with_images_and_safe_html(): void
    {
        $this->actingAs($this->editor)->post(route('activities.store'), $this->payload([
            'images' => [UploadedFile::fake()->image('a.jpg', 2400, 1600), UploadedFile::fake()->image('b.jpg', 800, 600)],
        ]))->assertRedirect(route('activities.edit', Activity::first()));

        $activity = Activity::first();
        $this->assertSame('hagia-sophia-skip-the-line', $activity->slug);
        $this->assertSame('Hagia Sophia Skip the Line', $activity->getTranslation('name', 'en'));
        $this->assertSame('<h2>About</h2><p>Great <strong>tour</strong></p>', $activity->description);
        $this->assertSame(1, $activity->sort_order);
        $this->assertCount(2, $activity->images);

        // 2400 px görsel 1600 px'e küçültülür
        [$width] = getimagesizefromstring(Storage::disk('media')->get($activity->cover->path));
        $this->assertSame(1600, $width);
    }

    public function test_update_keeps_other_translations_and_manages_images(): void
    {
        $activity = Activity::create([
            'city_id' => $this->city->id, 'name' => ['en' => 'Old name', 'tr' => 'Eski ad'], 'slug' => 'old-name', 'type' => 'product',
        ]);
        $first = $activity->images()->create(['path' => 'activities/1.webp', 'sort_order' => 0]);
        $second = $activity->images()->create(['path' => 'activities/2.webp', 'sort_order' => 1]);
        Storage::disk('media')->put('activities/1.webp', 'x');

        $this->actingAs($this->editor)->put(route('activities.update', $activity), $this->payload([
            'name' => 'New name', 'slug' => 'new-name', 'cover_image_id' => $second->id, 'remove_images' => [$first->id],
        ]))->assertRedirect(route('activities.edit', $activity));

        $activity->refresh();
        $this->assertSame('New name', $activity->getTranslation('name', 'en'));
        $this->assertSame('Eski ad', $activity->getTranslation('name', 'tr')); // Türkçe çeviri korunur
        $this->assertTrue($activity->cover->is($second));
        $this->assertModelMissing($first);
        Storage::disk('media')->assertMissing('activities/1.webp');
    }

    public function test_images_follow_dragged_order_including_new_uploads(): void
    {
        $activity = Activity::create(['city_id' => $this->city->id, 'name' => ['en' => 'X'], 'slug' => 'x', 'type' => 'product']);
        $a = $activity->images()->create(['path' => 'activities/a.webp', 'sort_order' => 0]);
        $b = $activity->images()->create(['path' => 'activities/b.webp', 'sort_order' => 1]);

        $this->actingAs($this->editor)->put(route('activities.update', $activity), $this->payload([
            'name' => 'X', 'slug' => 'x',
            'images' => [UploadedFile::fake()->image('n0.jpg'), UploadedFile::fake()->image('n1.jpg')],
            'image_order' => ['new:1', "id:{$b->id}", 'new:0', "id:{$a->id}"],
        ]))->assertRedirect(route('activities.edit', $activity));

        $ordered = $activity->images()->orderBy('sort_order')->get();
        $this->assertCount(4, $ordered);
        $this->assertTrue($ordered[1]->is($b));
        $this->assertTrue($ordered[3]->is($a));
        $this->assertSame([0, 1, 2, 3], $ordered->pluck('sort_order')->all());
        $this->assertGreaterThan($ordered[2]->id, $ordered[0]->id); // kapak = ikinci yeni dosya
    }

    public function test_validation_and_unique_slug(): void
    {
        Activity::create(['city_id' => $this->city->id, 'name' => ['en' => 'X'], 'slug' => 'hagia-sophia-skip-the-line', 'type' => 'product']);

        $this->actingAs($this->editor)->post(route('activities.store'), $this->payload(['affiliate_url' => 'not-a-url', 'type' => 'boat']))
            ->assertSessionHasErrors(['slug', 'affiliate_url', 'type']);
    }

    public function test_drag_reorder_within_city_and_toggle_popular_once(): void
    {
        $make = fn ($name, $sort) => Activity::create(['city_id' => $this->city->id, 'slug' => \Illuminate\Support\Str::slug($name), 'type' => 'product', 'name' => ['en' => $name], 'sort_order' => $sort]);
        $a = $make('A', 0);
        $b = $make('B', 0); // eski kayıtlar: aynı sıra numarası
        $c = $make('C', 0);

        $this->actingAs($this->editor)->get(route('activities.index'))->assertOk()->assertSee('data-order-url', false);
        $this->actingAs($this->editor)->patchJson(route('activities.reorder'), ['order' => [$c->id, $a->id, $b->id]])->assertOk();
        $this->assertSame(['C', 'A', 'B'], Activity::orderBy('sort_order')->get()->pluck('name')->all());

        // görünen ikisinin yeri değişir, diğeri yerinde kalır
        $this->actingAs($this->editor)->patchJson(route('activities.reorder'), ['order' => [$b->id, $c->id]])->assertOk();
        $this->assertSame(['B', 'A', 'C'], Activity::orderBy('sort_order')->get()->pluck('name')->all());

        $this->actingAs($this->editor)->patchJson(route('activities.popular', $a))->assertJson(['active' => true]);
        $this->assertTrue($a->fresh()->is_popular);
    }

    public function test_list_filters_and_toggle(): void
    {
        $activity = Activity::create(['city_id' => $this->city->id, 'partner_id' => $this->partner->id, 'name' => ['en' => 'Bosphorus Cruise'], 'slug' => 'bosphorus-cruise', 'type' => 'product']);
        Activity::create(['city_id' => $this->city->id, 'name' => ['en' => 'Topkapi Palace'], 'slug' => 'topkapi', 'type' => 'product', 'active' => true]);

        $this->actingAs($this->editor)->get(route('activities.index', ['search' => 'bosphorus']))
            ->assertOk()->assertSee('Bosphorus Cruise')->assertDontSee('Topkapi Palace');

        $this->actingAs($this->editor)->get(route('activities.index', ['status' => 'active']))
            ->assertSee('Topkapi Palace')->assertDontSee('Bosphorus Cruise');

        $this->actingAs($this->editor)->patchJson(route('activities.toggle', $activity))->assertJson(['active' => true]);
    }

    public function test_activity_with_clicks_cannot_be_deleted_and_city_is_protected(): void
    {
        $activity = Activity::create(['city_id' => $this->city->id, 'name' => ['en' => 'X'], 'slug' => 'x', 'type' => 'product']);
        AffiliateClick::create(['visitor_id' => 'v', 'session_id' => 's', 'partner' => 'getyourguide', 'activity_id' => $activity->id, 'path' => '/x', 'target_url' => 'https://x.com', 'created_at' => now()]);

        $this->actingAs($this->editor)->delete(route('activities.destroy', $activity))->assertSessionHas('error');
        $this->assertModelExists($activity);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->delete(route('cities.destroy', $this->city))->assertSessionHas('error');
        $this->assertModelExists($this->city);
    }

    public function test_wizard_pages_render(): void
    {
        $activity = Activity::create(['city_id' => $this->city->id, 'name' => ['en' => 'X', 'de' => 'Y'], 'slug' => 'x', 'type' => 'pass']);

        $this->actingAs($this->editor)->get(route('activities.create'))
            ->assertOk()->assertSee('data-wizard', false)->assertSee('Onay')->assertSee('data-free="0"', false);

        $this->actingAs($this->editor)->get(route('activities.edit', $activity))
            ->assertOk()->assertSee('data-free="1"', false)->assertDontSee('>Onay<', false)->assertSee(__('Çeviriler'));
    }

    public function test_sanitizer_blocks_scripts_and_javascript_links(): void
    {
        $this->assertSame('<p>Hi <a href="#">x</a></p>', HtmlSanitizer::clean('<p style="color:red">Hi <a href="javascript:alert(1)">x</a></p><iframe src="x"></iframe>'));
        $this->assertNull(HtmlSanitizer::clean('<p><br></p>'));
    }
    public function test_popular_can_be_toggled_from_the_list_and_filtered(): void
    {
        $a = Activity::create(['city_id' => $this->city->id, 'partner_id' => $this->partner->id, 'name' => ['en' => 'Tour A'], 'slug' => 'tour-a', 'type' => 'product']);
        Activity::create(['city_id' => $this->city->id, 'name' => ['en' => 'Pass B'], 'slug' => 'pass-b', 'type' => 'pass', 'is_popular' => true]);

        $this->actingAs($this->editor)->patchJson(route('activities.popular', $a))->assertJson(['active' => true]);
        $this->assertTrue($a->fresh()->is_popular);

        $this->actingAs($this->editor)->get(route('activities.index', ['type' => 'pass']))->assertSee('Pass B')->assertDontSee('Tour A');
        $this->actingAs($this->editor)->get(route('activities.index', ['partner' => $this->partner->id]))->assertSee('Tour A')->assertDontSee('Pass B');

        $this->actingAs($this->editor)->patchJson(route('activities.popular', $a));
        $this->actingAs($this->editor)->get(route('activities.index', ['popular' => 'yes']))->assertSee('Pass B')->assertDontSee('Tour A');
    }
}
