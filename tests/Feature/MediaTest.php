<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Blog;
use App\Models\City;
use App\Models\Country;
use App\Models\User;
use App\Services\ImageUploader;
use App\Support\HtmlSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('media');
    }

    public function test_activity_image_is_saved_in_dated_folder_with_small_copy_and_size(): void
    {
        $saved = app(ImageUploader::class)->activity(UploadedFile::fake()->image('a.jpg', 2400, 1600));

        $this->assertMatchesRegularExpression('#^activities/\d{4}/\d{2}/[a-z0-9]{20}\.webp$#', $saved['path']);
        $this->assertSame([1600, 1067], [$saved['width'], $saved['height']]);

        $small = str_replace('.webp', '-sm.webp', $saved['path']);
        Storage::disk('media')->assertExists([$saved['path'], $small]);
        [$width] = getimagesizefromstring(Storage::disk('media')->get($small));
        $this->assertSame(ImageUploader::SMALL, $width);
        $this->assertStringEndsWith('-sm.webp', ImageUploader::url($saved['path'], 'sm'));
    }

    public function test_deleting_an_image_removes_its_small_copy_too(): void
    {
        $uploader = app(ImageUploader::class);
        $path = $uploader->cover(UploadedFile::fake()->image('c.jpg', 1200, 800));

        $uploader->delete($path);

        Storage::disk('media')->assertMissing([$path, str_replace('.webp', '-sm.webp', $path)]);
    }

    public function test_editor_image_upload_returns_media_url(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('editor-images.store'), ['image' => UploadedFile::fake()->image('x.png', 2000, 1000)])
            ->assertOk()
            ->assertJsonPath('width', 1200)
            ->assertJson(fn ($json) => $json->where('url', fn ($url) => str_starts_with($url, ImageUploader::baseUrl() . 'content/'))->etc());

        $this->actingAs(User::factory()->create())
            ->postJson(route('editor-images.store'), ['image' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])
            ->assertUnprocessable();
    }

    public function test_sanitizer_keeps_only_our_images(): void
    {
        $ours = ImageUploader::baseUrl() . 'content/2026/11/abc.webp';
        $html = '<p>Hi</p><img src="' . $ours . '" alt="Bosphorus" onerror="x()" width="10">'
            . '<img src="data:image/png;base64,AAAA"><img src="https://evil.example/a.png">';

        $this->assertSame('<p>Hi</p><img src="' . $ours . '" alt="Bosphorus" loading="lazy">', HtmlSanitizer::clean($html));
        $this->assertSame(['content/2026/11/abc.webp'], HtmlSanitizer::imagePaths($html));
    }

    public function test_prune_deletes_only_old_unused_files(): void
    {
        $disk = Storage::disk('media');
        $country = Country::create(['code' => 'TR', 'name' => 'Türkiye', 'slug' => 'turkiye']);
        $city = City::create(['country_id' => $country->id, 'name' => 'Istanbul', 'slug' => 'istanbul']);
        $activity = Activity::create(['city_id' => $city->id, 'name' => ['en' => 'A'], 'slug' => 'a', 'type' => 'product']);
        $activity->images()->create(['path' => 'activities/2026/01/usedusedusedusedused.webp']);
        Blog::create(['title' => ['en' => 'B'], 'slug' => 'b', 'content' => ['en' => '<img src="' . ImageUploader::baseUrl() . 'content/2026/01/inlineinlineinlinein.webp">']]);

        foreach (['activities/2026/01/usedusedusedusedused.webp', 'activities/2026/01/usedusedusedusedused-sm.webp', 'content/2026/01/inlineinlineinlinein.webp', 'content/2026/01/orphanorphanorphanor.webp', 'blogs/2026/01/freshfreshfreshfresh.webp'] as $file) {
            $disk->put($file, 'x');
        }
        // "fresh" yeni yüklendi (form hâlâ açık olabilir), diğerleri eski
        foreach (['activities/2026/01/usedusedusedusedused.webp', 'activities/2026/01/usedusedusedusedused-sm.webp', 'content/2026/01/inlineinlineinlinein.webp', 'content/2026/01/orphanorphanorphanor.webp'] as $file) {
            touch($disk->path($file), now()->subDays(2)->getTimestamp());
        }

        $this->artisan('media:prune')->assertSuccessful();

        $disk->assertMissing('content/2026/01/orphanorphanorphanor.webp');
        $disk->assertExists(['activities/2026/01/usedusedusedusedused.webp', 'activities/2026/01/usedusedusedusedused-sm.webp', 'content/2026/01/inlineinlineinlinein.webp', 'blogs/2026/01/freshfreshfreshfresh.webp']);
    }
}
