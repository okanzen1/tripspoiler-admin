<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\City;
use App\Models\Country;
use App\Models\Faq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;
    private Activity $activity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->editor = User::factory()->create(); // SSS'leri herkes yönetir
        $country = Country::create(['code' => 'TR', 'name' => 'Türkiye', 'slug' => 'turkiye', 'active' => true]);
        $city = City::create(['country_id' => $country->id, 'name' => 'Istanbul', 'slug' => 'istanbul', 'active' => true]);
        $this->activity = Activity::create(['city_id' => $city->id, 'name' => ['en' => 'Bosphorus Cruise'], 'slug' => 'bosphorus-cruise', 'type' => 'product']);
    }

    public function test_editor_can_create_general_and_activity_faqs(): void
    {
        $this->actingAs($this->editor)->post(route('faqs.store'), [
            'question' => 'What is TripSpoiler?', 'answer' => 'A travel guide.', 'target' => 'general', 'active' => '1',
        ])->assertRedirect(route('faqs.edit', 1));

        $this->actingAs($this->editor)->post(route('faqs.store'), [
            'question' => 'How long is the cruise?', 'answer' => 'About 90 minutes.', 'target' => 'activity:' . $this->activity->id, 'active' => '1',
        ]);

        $this->assertSame(1, Faq::general()->count());
        $this->assertSame('How long is the cruise?', $this->activity->faqs()->first()->question);
    }

    public function test_add_another_question_to_same_place(): void
    {
        $first = Faq::create(['activity_id' => $this->activity->id, 'question' => ['en' => 'First?'], 'answer' => ['en' => 'Yes.']]);

        $this->actingAs($this->editor)->get(route('faqs.edit', $first))->assertSee(route('faqs.create', ['like' => $first->id]), false);
        $this->actingAs($this->editor)->get(route('faqs.create', ['like' => $first->id]))
            ->assertOk()->assertSee('value="activity:' . $this->activity->id . '"', false)->assertSee('data-free="1"', false);

        $this->actingAs($this->editor)->post(route('faqs.store'), [
            'question' => 'Second?', 'answer' => 'Sure.', 'target' => 'activity:' . $this->activity->id, 'active' => '1',
        ]);
        $this->assertSame(['First?', 'Second?'], $this->activity->faqs()->orderBy('sort_order')->get()->pluck('question')->all());
    }

    public function test_bulk_add_pastes_many_questions_to_one_place(): void
    {
        Faq::create(['activity_id' => $this->activity->id, 'question' => ['en' => 'First?'], 'answer' => ['en' => 'Yes.']]);
        $this->actingAs($this->editor)->get(route('faqs.create', ['bulk' => 1]))->assertOk()->assertSee('name="bulk"', false);

        $this->actingAs($this->editor)->post(route('faqs.store'), [
            'bulk' => "**1. Q: How do I use a code?**\nA: Copy it.\n\n2. Q: Is it free?\nA: Yes.\nAlways.",
            'target' => 'activity:' . $this->activity->id, 'active' => '1',
        ])->assertRedirect(route('faqs.index'))->assertSessionHas('success');

        $faqs = $this->activity->faqs()->orderBy('sort_order')->get();
        $this->assertSame(['First?', 'How do I use a code?', 'Is it free?'], $faqs->pluck('question')->all());
        $this->assertSame("Yes.\nAlways.", $faqs->last()->answer);

        // numaralı biçim + bulunamayan metin
        $this->assertCount(2, \App\Support\FaqParser::parse("1. Open?\n\nYes, daily.\n\n2. Price?\nFree."));
        $this->actingAs($this->editor)->post(route('faqs.store'), ['bulk' => 'just some text', 'target' => 'general'])->assertSessionHasErrors('bulk');
    }

    public function test_list_filters_by_scope_and_search(): void
    {
        Faq::create(['question' => ['en' => 'General question'], 'answer' => ['en' => 'x']]);
        Faq::create(['activity_id' => $this->activity->id, 'question' => ['en' => 'Cruise question'], 'answer' => ['en' => 'y']]);

        $this->actingAs($this->editor)->get(route('faqs.index', ['scope' => 'general']))
            ->assertOk()->assertSee('General question')->assertDontSee('Cruise question');

        $this->actingAs($this->editor)->get(route('faqs.index', ['search' => 'cruise']))
            ->assertSee('Cruise question')->assertDontSee('General question')->assertSee('Bosphorus Cruise');
    }

    public function test_update_keeps_translations_and_toggle_works(): void
    {
        $faq = Faq::create(['question' => ['en' => 'Old?', 'tr' => 'Eski?'], 'answer' => ['en' => 'a', 'tr' => 'b']]);

        $this->actingAs($this->editor)->put(route('faqs.update', $faq), ['question' => 'New?', 'answer' => 'c', 'target' => 'general', 'active' => '1']);

        $faq->refresh();
        $this->assertSame('New?', $faq->getTranslation('question', 'en'));
        $this->assertSame('Eski?', $faq->getTranslation('question', 'tr'));

        $this->actingAs($this->editor)->patchJson(route('faqs.toggle', $faq))->assertJson(['active' => false]);
    }

    public function test_activity_wizard_syncs_faqs_in_order(): void
    {
        $keep = Faq::create(['activity_id' => $this->activity->id, 'question' => ['en' => 'Keep?', 'de' => 'Behalten?'], 'answer' => ['en' => 'yes'], 'sort_order' => 0]);
        $drop = Faq::create(['activity_id' => $this->activity->id, 'question' => ['en' => 'Drop?'], 'answer' => ['en' => 'no'], 'sort_order' => 1]);

        $this->actingAs($this->editor)->put(route('activities.update', $this->activity), [
            'name' => 'Bosphorus Cruise', 'slug' => 'bosphorus-cruise', 'type' => 'product', 'city_id' => $this->activity->city_id,
            'faqs' => [
                ['id' => '', 'question' => 'New first?', 'answer' => 'Sure.'],
                ['id' => $keep->id, 'question' => 'Keep (edited)?', 'answer' => 'yes'],
                ['id' => '', 'question' => '', 'answer' => ''], // boş satır atlanır
            ],
        ])->assertRedirect(route('activities.edit', $this->activity));

        $faqs = $this->activity->faqs()->get();
        $this->assertSame(['New first?', 'Keep (edited)?'], $faqs->pluck('question')->all());
        $this->assertSame('Behalten?', $faqs[1]->getTranslation('question', 'de')); // çeviri korunur
        $this->assertModelMissing($drop);
    }

    public function test_activity_faq_requires_both_question_and_answer(): void
    {
        $this->actingAs($this->editor)->put(route('activities.update', $this->activity), [
            'name' => 'Bosphorus Cruise', 'slug' => 'bosphorus-cruise', 'type' => 'product', 'city_id' => $this->activity->city_id,
            'faqs' => [['question' => 'Only question?', 'answer' => '']],
        ])->assertSessionHasErrors('faqs.0.answer');
    }

    public function test_deleting_activity_removes_its_faqs(): void
    {
        $faq = Faq::create(['activity_id' => $this->activity->id, 'question' => ['en' => 'Q'], 'answer' => ['en' => 'A']]);

        $this->actingAs($this->editor)->delete(route('activities.destroy', $this->activity));

        $this->assertModelMissing($faq);
    }

    public function test_blog_faqs_via_wizard_and_faq_menu(): void
    {
        $blog = \App\Models\Blog::create(['title' => ['en' => 'Istanbul Guide'], 'slug' => 'istanbul-guide']);

        // Blog sihirbazından
        $this->actingAs($this->editor)->put(route('blogs.update', $blog), [
            'title' => 'Istanbul Guide', 'slug' => 'istanbul-guide', 'active' => '0',
            'faqs' => [['id' => '', 'question' => 'Best time to visit?', 'answer' => 'Spring.']],
        ])->assertRedirect(route('blogs.edit', $blog));
        $this->assertSame('Best time to visit?', $blog->faqs()->first()->question);

        // SSS menüsünden aynı bloga
        $this->actingAs($this->editor)->post(route('faqs.store'), [
            'question' => 'Is it safe?', 'answer' => 'Yes.', 'target' => "blog:{$blog->id}", 'active' => '1',
        ]);
        $this->assertSame(2, $blog->faqs()->count());
        $this->assertSame(0, Faq::general()->count());

        $this->actingAs($this->editor)->get(route('faqs.index', ['scope' => 'blog']))
            ->assertOk()->assertSee('Is it safe?')->assertSee('Blog · Istanbul Guide');

        // Geçersiz yer reddedilir
        $this->actingAs($this->editor)->post(route('faqs.store'), ['question' => 'x', 'answer' => 'y', 'target' => 'blog:999'])
            ->assertSessionHasErrors('target');
    }

    public function test_drag_reorder_swaps_positions_within_same_place(): void
    {
        $a = Faq::create(['question' => ['en' => 'A?'], 'answer' => ['en' => 'a'], 'sort_order' => 1, 'active' => true]);
        $b = Faq::create(['question' => ['en' => 'B?'], 'answer' => ['en' => 'b'], 'sort_order' => 2, 'active' => true]);
        $c = Faq::create(['question' => ['en' => 'C?'], 'answer' => ['en' => 'c'], 'sort_order' => 2, 'active' => true]);
        $other = Faq::create(['question' => ['en' => 'P?'], 'answer' => ['en' => 'p'], 'page_key' => 'about', 'sort_order' => 1, 'active' => true]);

        $this->actingAs($this->editor)->patchJson(route('faqs.reorder'), ['order' => [$c->id, $a->id, $b->id]])->assertOk();
        $this->assertSame([$c->id, $a->id, $b->id], Faq::general()->orderBy('sort_order')->orderBy('id')->pluck('id')->all());

        // Farklı yerdeki sorular birlikte sıralanamaz
        $this->actingAs($this->editor)->patchJson(route('faqs.reorder'), ['order' => [$a->id, $other->id]])->assertStatus(422);
    }

    public function test_faq_section_texts_are_editable(): void
    {
        $this->actingAs($this->editor)->get(route('faqs.texts'))->assertOk()->assertSee('Still have a question?');
        $this->actingAs($this->editor)->put(route('faqs.texts.update'), [
            'faq' => ['title' => 'Got questions?', 'title_highlight' => 'We answer.'],
            'faq_contact' => ['title' => 'Ask us', 'eyebrow' => 'Write'],
        ])->assertRedirect();
        $this->assertSame('Got questions?', \App\Models\SectionText::for('faq')->getTranslation('title', 'en', false));
        $this->assertSame('Write', \App\Models\SectionText::for('faq_contact')->getTranslation('eyebrow', 'en', false));
        $this->actingAs($this->editor)->get(route('faqs.texts'))->assertOk()->assertSee('Çeviriler');
    }
}
