<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Blog;
use App\Models\City;
use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;
    private City $city;
    private Activity $activity;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('media');
        $this->editor = User::factory()->create(); // blogu herkes yönetir
        $country = Country::create(['code' => 'TR', 'name' => 'Türkiye', 'slug' => 'turkiye', 'active' => true]);
        $this->city = City::create(['country_id' => $country->id, 'name' => 'Istanbul', 'slug' => 'istanbul', 'active' => true]);
        $this->activity = Activity::create(['city_id' => $this->city->id, 'name' => ['en' => 'Hagia Sophia Tour'], 'slug' => 'hagia-sophia', 'type' => 'product']);
    }

    public function test_editor_can_create_blog_with_cover_tags_and_activities(): void
    {
        $this->actingAs($this->editor)->post(route('blogs.store'), [
            'title' => 'Hagia Sophia Guide',
            'city_id' => $this->city->id,
            'excerpt' => 'Everything you need to know.',
            'content' => '<h2>History</h2><p>Built in 537.</p><script>x</script>',
            'tags' => 'History, Istanbul, history, ',
            'cover' => UploadedFile::fake()->image('cover.jpg', 2000, 1000),
            'activity_ids' => [$this->activity->id],
            'active' => '1',
        ])->assertRedirect(route('blogs.index'));

        $blog = Blog::first();
        $this->assertSame('hagia-sophia-guide', $blog->slug);
        $this->assertSame('<h2>History</h2><p>Built in 537.</p>', $blog->content);
        $this->assertSame(['History', 'Istanbul'], $blog->tags); // tekrar eden etiket tek kalır
        $this->assertNotNull($blog->published_at); // yayına alınınca tarih yazılır
        $this->assertTrue($blog->activities->first()->is($this->activity));
        Storage::disk('media')->assertExists($blog->cover_path);
    }

    public function test_update_keeps_translations_and_can_remove_cover(): void
    {
        Storage::disk('media')->put('blogs/old.webp', 'x');
        $blog = Blog::create(['title' => ['en' => 'Old', 'tr' => 'Eski'], 'slug' => 'old', 'cover_path' => 'blogs/old.webp']);

        $this->actingAs($this->editor)->put(route('blogs.update', $blog), [
            'title' => 'New', 'slug' => 'new', 'remove_cover' => '1', 'active' => '0',
        ])->assertRedirect(route('blogs.edit', $blog));

        $blog->refresh();
        $this->assertSame('New', $blog->getTranslation('title', 'en'));
        $this->assertSame('Eski', $blog->getTranslation('title', 'tr'));
        $this->assertNull($blog->cover_path);
        Storage::disk('media')->assertMissing('blogs/old.webp');
    }

    public function test_list_search_toggle_and_delete(): void
    {
        $blog = Blog::create(['title' => ['en' => 'Bosphorus Guide'], 'slug' => 'bosphorus']);
        Blog::create(['title' => ['en' => 'Topkapi Guide'], 'slug' => 'topkapi']);

        $this->actingAs($this->editor)->get(route('blogs.index', ['search' => 'bosphorus']))
            ->assertOk()->assertSee('Bosphorus Guide')->assertDontSee('Topkapi Guide');

        $this->actingAs($this->editor)->patchJson(route('blogs.toggle', $blog))->assertJson(['active' => true]);

        $this->actingAs($this->editor)->delete(route('blogs.destroy', $blog))->assertRedirect(route('blogs.index'));
        $this->assertModelMissing($blog);
    }

    public function test_wizard_pages_render(): void
    {
        $blog = Blog::create(['title' => ['en' => 'X', 'de' => 'Y'], 'slug' => 'x']);

        $this->actingAs($this->editor)->get(route('blogs.create'))->assertOk()->assertSee('data-free="0"', false)->assertSee('Hagia Sophia Tour');
        $this->actingAs($this->editor)->get(route('blogs.edit', $blog))->assertOk()->assertSee('data-free="1"', false)->assertSee(__('Çeviriler'));
    }
}
