<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PageSeeder::class);
    }

    public function test_only_admin_manages_pages(): void
    {
        $this->actingAs(User::factory()->create())->get(route('pages.index'))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('pages.index'))
            ->assertOk()->assertSee('Anasayfa')->assertSee('Gizlilik politikası')->assertSee('8 sayfanın SEO bilgisi eksik');
    }

    public function test_admin_updates_seo_and_keeps_other_languages(): void
    {
        $page = Page::where('key', 'about')->first();
        $page->setTranslation('meta_title', 'tr', 'Hakkımızda | TripSpoiler')->save();

        $this->actingAs(User::factory()->admin()->create())->put(route('pages.update', $page), [
            'meta_title' => 'About TripSpoiler',
            'meta_description' => 'Honest travel guides.',
        ])->assertRedirect(route('pages.index'));

        $page->refresh();
        $this->assertSame('About TripSpoiler', $page->meta_title);
        $this->assertSame('Hakkımızda | TripSpoiler', $page->getTranslation('meta_title', 'tr'));
        $this->assertTrue($page->isSeoComplete());
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(PageSeeder::class);

        $this->assertSame(8, Page::count());
    }
}
