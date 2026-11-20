<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Attraction;
use App\Models\Blog;
use App\Models\City;
use App\Models\CityGuide;
use App\Models\CityGuideSection;
use App\Models\CityGuideTopic;
use App\Models\Country;
use App\Models\User;
use App\Support\ContentTodo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentTodoTest extends TestCase
{
    use RefreshDatabase;

    private City $city;

    protected function setUp(): void
    {
        parent::setUp();
        $country = Country::create(['code' => 'TR', 'name' => 'Turkey', 'slug' => 'turkey', 'active' => true]);
        $this->city = City::create(['country_id' => $country->id, 'name' => 'Istanbul', 'slug' => 'istanbul', 'active' => true]);
        ContentTodo::forget();
    }

    public function test_lists_records_with_empty_seo_or_content_and_drops_them_when_filled(): void
    {
        $activity = Activity::create(['city_id' => $this->city->id, 'name' => ['en' => 'Topkapi ticket'], 'slug' => 'topkapi-ticket', 'type' => 'product',
            'description' => ['en' => '<p>Text</p>'], 'meta_title' => ['en' => 'Title']]);
        $place = Attraction::create(['city_id' => $this->city->id, 'slug' => 'topkapi', 'name' => ['en' => 'Topkapi Palace'],
            'summary' => ['en' => 'Sum'], 'content' => ['en' => '<p></p>'], 'meta_title' => ['en' => 'T'], 'meta_description' => ['en' => 'D']]);

        $this->assertSame([__('SEO açıklaması')], ContentTodo::missing('activity', $activity->id));
        $this->assertSame([__('Yazı')], ContentTodo::missing('attraction', $place->id)); // boş HTML yazı sayılmaz
        $this->assertSame(1, ContentTodo::area('activities'));

        $activity->update(['meta_description' => ['en' => 'Description']]);
        $this->assertSame([], ContentTodo::missing('activity', $activity->id));
        $this->assertSame(0, ContentTodo::area('activities'));
    }

    public function test_city_guide_lists_unwritten_topics(): void
    {
        CityGuideTopic::query()->update(['active' => false]); // kurulumla gelen alanlar: kapalı alanlar sayılmaz
        $food = CityGuideTopic::create(['key' => 'test_food', 'name' => ['en' => 'Food & drink'], 'sort_order' => 1, 'active' => true]);
        CityGuide::create(['city_id' => $this->city->id, 'title' => ['en' => 'Istanbul guide'], 'description' => ['en' => 'Intro']]);
        $section = CityGuideSection::create(['city_id' => $this->city->id, 'topic_id' => $food->id, 'title' => ['en' => 'Food'], 'active' => true]);

        $guide = ContentTodo::items()->firstWhere('type', 'city-guide');
        $this->assertSame([__('Boş alan: :list', ['list' => 'Food & drink'])], $guide['missing']);
        $this->assertSame([__('Yazı')], ContentTodo::missing('city-guide-section', $section->id));

        $section->update(['content' => ['en' => '<p>Kebabs</p>']]);
        $this->assertSame(0, ContentTodo::area('city-guides'));
    }

    public function test_panel_shows_the_warning_in_menu_list_and_edit_form(): void
    {
        $blog = Blog::create(['title' => ['en' => 'Best time to visit'], 'slug' => 'best-time', 'content' => ['en' => '<p>Text</p>']]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('blogs.index'))->assertOk()
            ->assertSee(__('SEO\'su ya da içeriği eksik olanlar (:count)', ['count' => 1]))
            ->assertSee('Best time to visit');
        $this->get(route('blogs.edit', $blog))->assertOk()
            ->assertSee(__('Eksik: :list', ['list' => implode(', ', [__('SEO başlığı'), __('SEO açıklaması'), __('Özet')])]));
    }
}
