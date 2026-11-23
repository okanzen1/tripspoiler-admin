<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HeroXsTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_phone_copy_of_current_hero(): void
    {
        Storage::fake('media');
        $gd = imagecreatetruecolor(2000, 1000);
        ob_start();
        imagewebp($gd);
        Storage::disk('media')->put('hero/2026/11/abc.webp', ob_get_clean());
        Setting::put('hero_image', 'hero/2026/11/abc.webp');

        $this->artisan('media:hero-xs')->assertSuccessful();

        Storage::disk('media')->assertExists('hero/2026/11/abc-xs.webp');
        $this->assertSame(828, getimagesizefromstring(Storage::disk('media')->get('hero/2026/11/abc-xs.webp'))[0]);
        $this->assertSame('hero/2026/11/abc.webp', Setting::get('hero_image_xs'));
    }
}
