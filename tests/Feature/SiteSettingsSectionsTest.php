<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class SiteSettingsSectionsTest extends TestCase
{
    use RefreshDatabase;
    public function test_sections_page_lists_faq_and_popular(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('site-settings.index'))
            ->assertOk()->assertSee('Sıkça sorulan sorular')->assertSee('Popüler aktiviteler');
        $this->actingAs(User::factory()->admin()->create())->patchJson(route('site-settings.section', 'home_faq'))->assertOk()->assertJson(['active' => false]);
    }
}
