<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\City;
use App\Models\Country;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;
    private Activity $activity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->editor = User::factory()->create(); // yorumları herkes yönetir
        $country = Country::create(['code' => 'TR', 'name' => 'Türkiye', 'slug' => 'turkiye', 'active' => true]);
        $city = City::create(['country_id' => $country->id, 'name' => 'Istanbul', 'slug' => 'istanbul', 'active' => true]);
        $this->activity = Activity::create(['city_id' => $city->id, 'name' => ['en' => 'Bosphorus Cruise'], 'slug' => 'bosphorus-cruise', 'type' => 'product']);
    }

    public function test_editor_creates_review_with_encrypted_email(): void
    {
        $this->actingAs($this->editor)->post(route('reviews.store'), [
            'name' => 'Emily Carter', 'email' => 'Emily@Example.com', 'rating' => 4, 'comment' => 'Great cruise!',
            'activity_id' => $this->activity->id, 'approved' => '1',
        ])->assertRedirect(route('reviews.index'));

        $review = Review::first();
        $this->assertSame(4, $review->rating);
        $this->assertTrue($review->activity->is($this->activity));
        $this->assertSame(\App\Support\Privacy::hash('emily@example.com'), $review->email_hash);
        $this->assertNotSame('Emily@Example.com', \DB::table('reviews')->value('email'));
    }

    public function test_list_shows_pending_first_and_approval_toggle(): void
    {
        Review::create(['name' => 'Approved Person', 'rating' => 5, 'comment' => 'ok', 'approved' => true]);
        $pending = Review::create(['name' => 'Pending Person', 'rating' => 2, 'comment' => 'hmm']);

        $this->actingAs($this->editor)->get(route('reviews.index'))
            ->assertOk()->assertSeeInOrder(['Pending Person', 'Approved Person'])->assertSee('Onay bekliyor');

        $this->actingAs($this->editor)->get(route('reviews.index', ['status' => 'pending']))
            ->assertSee('Pending Person')->assertDontSee('Approved Person');

        $this->actingAs($this->editor)->patchJson(route('reviews.toggle', $pending))->assertJson(['active' => true]);
        $this->assertTrue($pending->fresh()->approved);
    }

    public function test_validation(): void
    {
        $this->actingAs($this->editor)->post(route('reviews.store'), ['name' => '', 'rating' => 7, 'comment' => ''])
            ->assertSessionHasErrors(['name', 'rating', 'comment']);
    }

    public function test_activity_wizard_approves_adds_and_removes_reviews(): void
    {
        $pending = Review::create(['activity_id' => $this->activity->id, 'name' => 'Ana', 'email' => 'ana@example.com', 'rating' => 5, 'comment' => 'Lovely']);
        $spam = Review::create(['activity_id' => $this->activity->id, 'name' => 'Spam', 'rating' => 1, 'comment' => 'buy now']);

        $this->actingAs($this->editor)->put(route('activities.update', $this->activity), [
            'name' => 'Bosphorus Cruise', 'slug' => 'bosphorus-cruise', 'type' => 'product', 'city_id' => $this->activity->city_id,
            'reviews' => [
                ['id' => $pending->id, 'name' => 'Ana', 'rating' => 5, 'comment' => 'Lovely', 'approved' => '1'],
                ['id' => '', 'name' => 'Manual Guest', 'rating' => 4, 'comment' => 'Added by admin', 'approved' => '1'],
            ],
        ])->assertRedirect(route('activities.edit', $this->activity));

        $this->assertTrue($pending->fresh()->approved);
        $this->assertSame('ana@example.com', $pending->fresh()->email); // e-posta korunur
        $this->assertModelMissing($spam);
        $this->assertSame(2, $this->activity->reviews()->approved()->count());
    }

    public function test_sidebar_shows_pending_count(): void
    {
        Review::create(['name' => 'X', 'rating' => 3, 'comment' => 'y']);

        $this->actingAs($this->editor)->get(route('reviews.index'))->assertSee('title="Yorumlar (1)"', false);
    }

    public function test_personal_data_is_never_stored_in_plain_text(): void
    {
        $user = \App\Models\User::factory()->create(['first_name' => 'Okan', 'last_name' => 'Özen', 'email' => 'okan@example.com']);
        Review::create(['name' => 'Lukas Schneider', 'email' => 'lukas@example.com', 'rating' => 5, 'comment' => 'Great', 'approved' => true]);
        \App\Models\Visit::create(['visitor_id' => str_repeat('a', 32), 'session_id' => str_repeat('b', 32), 'ip' => '180.190.98.94', 'ip_hash' => \App\Support\Privacy::hash('180.190.98.94'), 'path' => '/en', 'page_type' => 'home']);

        $dump = json_encode([\DB::table('users')->get(), \DB::table('reviews')->get(), \DB::table('visits')->get()]);
        foreach (['Okan', 'okan@example.com', 'Lukas', 'Schneider', 'lukas@example.com', '180.190.98.94'] as $plain) {
            $this->assertStringNotContainsString($plain, $dump);
        }
        $this->assertSame('Lukas Schneider', Review::first()->name);
        $this->assertTrue(\App\Models\User::findByEmail('OKAN@example.com ')->is($user));
        $this->assertSame('180.190.98.94', \App\Models\Visit::first()->ip);
    }
}
