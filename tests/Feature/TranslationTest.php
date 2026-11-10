<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Jobs\TranslateModel;
use App\Models\Activity;
use App\Models\City;
use App\Models\Country;
use App\Models\Language;
use App\Models\Redirect;
use App\Models\TranslationState;
use App\Models\User;
use App\Services\Translation\GoogleTranslator;
use App\Services\Translation\ModelTranslator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Fakes\FakeGoogleTranslator;
use Tests\TestCase;

class TranslationTest extends TestCase
{
    use RefreshDatabase;

    private Activity $activity;

    private FakeGoogleTranslator $google;

    protected function setUp(): void
    {
        parent::setUp();

        $this->google = new FakeGoogleTranslator();
        $this->app->instance(GoogleTranslator::class, $this->google);

        foreach ([['en', 'English'], ['de', 'German'], ['tr', 'Turkish'], ['ja', 'Japanese']] as $i => [$code, $name]) {
            Language::create(['code' => $code, 'name' => $name, 'native_name' => $name, 'sort_order' => $i, 'active' => $code !== 'ja']);
        }
        $country = Country::create(['code' => 'TR', 'name' => 'Türkiye', 'slug' => 'turkiye', 'active' => true]);
        $city = City::create(['country_id' => $country->id, 'name' => 'Istanbul', 'slug' => 'istanbul', 'active' => true]);
        $this->activity = Activity::create([
            'city_id' => $city->id, 'slug' => 'hagia-sophia', 'type' => 'product',
            'name' => ['en' => 'Hagia Sophia', 'de' => 'Alte Übersetzung'],
            'description' => ['en' => '<h2>About</h2><p>Great <strong>tour</strong> &amp; <a href="https://x.com/a?b=1&amp;c=2">more</a></p><p><img src="/m.webp" alt=""></p>'],
        ]);
    }

    public function test_translates_missing_and_stale_languages_keeping_html_and_slug(): void
    {
        $translator = app(ModelTranslator::class);
        $this->assertSame(['de' => 'stale', 'tr' => 'missing'], $translator->status($this->activity)); // ja kapalı

        $this->assertSame(['de', 'tr'], $translator->translate($this->activity));

        $this->activity->refresh();
        $this->assertSame('[de] Hagia Sophia', $this->activity->getTranslation('name', 'de'));
        $this->assertSame(
            '<h2>[tr] About</h2><p>[tr] Great <strong>tour</strong> &amp; <a href="https://x.com/a?b=1&amp;c=2">more</a></p><p><img src="/m.webp" alt=""></p>',
            $this->activity->getTranslation('description', 'tr'),
        );
        $this->assertSame('hagia-sophia', $this->activity->slug);
        $this->assertSame(['de' => 'ok', 'tr' => 'ok'], $translator->status($this->activity));

        // Link adresi ve görsel çeviriye hiç gitmez
        $sent = collect($this->google->requests)->pluck('text')->implode("\n");
        $this->assertStringNotContainsString('x.com', $sent);
        $this->assertStringNotContainsString('m.webp', $sent);

        // İngilizce değişince çeviriler eskir
        $this->activity->setTranslation('name', 'en', 'Hagia Sophia Tour')->save();
        $this->assertSame(['de' => 'stale', 'tr' => 'stale'], $translator->status($this->activity->fresh()));
    }

    public function test_one_language_or_all_languages_from_the_record_page(): void
    {
        $editor = User::factory()->create();
        $url = route('translations.translate', ['activity', $this->activity->id]);

        $this->actingAs($editor)->from(route('activities.edit', $this->activity))->post($url, ['locale' => 'tr'])
            ->assertRedirect(route('activities.edit', $this->activity))->assertSessionHas('success');
        $this->assertSame(['tr'], TranslationState::pluck('locale')->all());
        $this->assertSame('Alte Übersetzung', $this->activity->fresh()->getTranslation('name', 'de')); // diğer dile dokunulmadı

        $this->actingAs($editor)->post($url)->assertSessionHas('success'); // eksikler: sadece de
        $this->assertEqualsCanonicalizing(['tr', 'de'], TranslationState::pluck('locale')->all());

        $this->google->requests = [];
        $this->actingAs($editor)->post($url, ['all' => 1])->assertSessionHas('success'); // hepsi yeniden
        $this->assertEqualsCanonicalizing(['de', 'tr'], collect($this->google->requests)->pluck('locale')->unique()->all());

        $this->actingAs($editor)->post($url, ['locale' => 'ja'])->assertSessionHasErrors('locale'); // kapalı dil
    }

    public function test_translation_errors_are_shown(): void
    {
        $this->google->fail = true;

        $this->actingAs(User::factory()->create())
            ->post(route('translations.translate', ['activity', $this->activity->id]))
            ->assertSessionHas('error');
        $this->assertSame(0, TranslationState::count());
    }

    public function test_changing_slug_redirects_old_address_in_every_active_language(): void
    {
        $this->activity->update(['slug' => 'hagia-sophia-tickets']);
        $this->activity->update(['slug' => 'hagia-sophia-skip-the-line']);

        $this->assertSame('/de/activities/hagia-sophia-skip-the-line', Redirect::where('from_path', '/de/activities/hagia-sophia')->value('to_path')); // zincir yok
        $this->assertSame('/en/activities/hagia-sophia-skip-the-line', Redirect::where('from_path', '/en/activities/hagia-sophia-tickets')->value('to_path'));
        $this->assertFalse(Redirect::where('from_path', 'like', '/ja/%')->exists()); // kapalı dil

        // Eski slug'a geri dönülürse o adrese giden yönlendirme kalkar
        $this->activity->update(['slug' => 'hagia-sophia']);
        $this->assertFalse(Redirect::where('from_path', '/de/activities/hagia-sophia')->exists());

        // Çok baytlı (eski çevrilmiş) adresler bozulmaz
        Redirect::point('https://tripspoiler.com/zh/activities/%E5%AF%BC%E6%B8%B8?x=1', '/zh/activities/hagia-sophia');
        $this->assertTrue(Redirect::where('from_path', '/zh/activities/导游')->exists());
    }

    public function test_settings_page_bulk_by_language_and_toggle(): void
    {
        Queue::fake();
        $admin = User::factory()->create(['role' => Role::Admin]);

        $this->actingAs($admin)->get(route('translations.index'))->assertOk()->assertSee('German')->assertSee(__(':count kayıt bekliyor', ['count' => 1]));

        $this->actingAs($admin)->post(route('translations.all'), ['locale' => 'tr'])->assertSessionHas('success');
        Queue::assertPushed(TranslateModel::class, fn (TranslateModel $job) => $job->locales === ['tr']);

        $this->actingAs($admin)->post(route('translations.all'))->assertSessionHas('success');
        Queue::assertPushed(TranslateModel::class, fn (TranslateModel $job) => $job->locales === ['de', 'tr']);

        $de = Language::where('code', 'de')->first();
        $this->actingAs($admin)->patchJson(route('languages.toggle', $de))->assertJson(['active' => false]);
        $this->actingAs($admin)->patchJson(route('languages.toggle', Language::where('code', 'en')->first()))->assertStatus(422);

        $this->actingAs(User::factory()->create())->get(route('translations.index'))->assertRedirect(route("analytics.index"));
    }
    public function test_translation_can_be_viewed_and_corrected_by_hand(): void
    {
        $editor = User::factory()->create();
        $url = route('translations.edit', ['activity', $this->activity->id, 'de']);

        $this->actingAs($editor)->get($url)->assertOk()
            ->assertSee('Alte Übersetzung')       // Almancası
            ->assertSee('Hagia Sophia')           // yanında İngilizcesi
            ->assertSee(route('activities.edit', $this->activity), false);

        $this->actingAs($editor)->from($url)->put($url, ['fields' => [
            'name' => ' Hagia Sophia Ticket ',
            'description' => '<p>Tolle <strong>Tour</strong></p><script>x()</script>',
        ]])->assertRedirect($url)->assertSessionHas('success');

        $this->activity->refresh();
        $this->assertSame('Hagia Sophia Ticket', $this->activity->getTranslation('name', 'de'));
        $this->assertSame('<p>Tolle <strong>Tour</strong></p>', $this->activity->getTranslation('description', 'de'));
        $this->assertSame('Hagia Sophia', $this->activity->getTranslation('name', 'en')); // İngilizceye dokunulmaz
        $this->assertSame('ok', app(ModelTranslator::class)->status($this->activity)['de']); // elle düzeltilen güncel sayılır
        $this->assertSame([], $this->google->requests);

        $this->actingAs($editor)->get(route('translations.edit', ['activity', $this->activity->id, 'ja']))->assertNotFound(); // kapalı dil
        $this->actingAs($editor)->get(route('translations.edit', ['activity', $this->activity->id, 'en']))->assertNotFound(); // kaynak dil
    }

    public function test_multiline_text_keeps_line_breaks_and_bullets(): void
    {
        $text = "We only collect:\n- Contact form: your name.\n\n- Reviews: your rating.\nThat is all.";
        $out = $this->google->translate([$text], 'tr')[0];

        $this->assertSame("[tr] We only collect:\n- [tr] Contact form: your name.\n\n- [tr] Reviews: your rating.\n[tr] That is all.", $out);
        $this->assertCount(1, $this->google->requests); // satırlar tek istekte
    }

    public function test_page_content_can_be_retranslated_when_up_to_date(): void
    {
        $this->seed(\Database\Seeders\PageSeeder::class);
        $page = \App\Models\Page::where('key', 'privacy')->firstOrFail();
        $record = \App\Models\SectionText::for('privacy_summary');
        $record->setTranslation('title', 'en', 'In short');
        $record->setTranslation('description', 'en', "One.\nTwo.");
        $record->save();
        $admin = \App\Models\User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('pages.content.translate', [$page, 'privacy_summary']))->assertRedirect();
        $this->assertSame("[tr] One.\n[tr] Two.", $record->fresh()->getTranslation('description', 'tr'));

        // Elle bozulmuş ama "güncel" görünen çeviri: all=1 baştan çevirir
        $record->refresh()->setTranslation('description', 'tr', 'Tek paragraf')->saveQuietly();
        $this->actingAs($admin)->post(route('pages.content.translate', [$page, 'privacy_summary']))->assertSessionHas('success', 'Tüm çeviriler zaten güncel.');
        $this->actingAs($admin)->post(route('pages.content.translate', [$page, 'privacy_summary']), ['all' => 1])->assertRedirect();
        $this->assertSame("[tr] One.\n[tr] Two.", $record->fresh()->getTranslation('description', 'tr'));
    }
}
