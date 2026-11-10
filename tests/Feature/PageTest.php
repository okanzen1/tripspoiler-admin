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
        ])->assertRedirect(route('pages.edit', $page)); // kaydedince sayfanın kartlarına döner

        $page->refresh();
        $this->assertSame('About TripSpoiler', $page->meta_title);
        $this->assertSame('Hakkımızda | TripSpoiler', $page->getTranslation('meta_title', 'tr'));
        $this->assertTrue($page->isSeoComplete());
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(PageSeeder::class);
        $count = Page::count();

        $this->seed(PageSeeder::class); // ikinci kez çalışınca kopya oluşmamalı

        $this->assertGreaterThan(0, $count);
        $this->assertSame($count, Page::count());
    }

    public function test_blog_post_page_has_sections_and_no_own_seo(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Page::where('key', 'post')->firstOrFail();

        $this->actingAs($admin)->get(route('pages.edit', $post))->assertOk()->assertSee('Blog yazılarına git');
        $this->actingAs($admin)->get(route('pages.sections', $post))->assertOk()->assertSee('Yazı içeriği')->assertDontSee('Blog yazıları');
        $this->actingAs($admin)->patchJson(route('pages.sections.update', $post), ['order' => ['faq', 'main', 'reviews'], 'on' => ['faq', 'reviews']])->assertOk();
        $this->assertSame(['faq', 'main', 'reviews'], array_slice(\App\Support\PageSections::config('post')['order'], 0, 3));
    }
}
