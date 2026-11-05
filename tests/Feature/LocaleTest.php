<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_panel_follows_user_locale(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $this->actingAs($user)->get(route('faqs.index'))
            ->assertOk()->assertSee('<html lang="en"', false)->assertSee('Frequently asked questions')->assertDontSee('Sıkça sorulan sorular');
    }

    public function test_switching_language_is_saved_on_user(): void
    {
        $user = User::factory()->create(['locale' => 'tr']);

        $this->actingAs($user)->from(route('faqs.index'))->post(route('locale.update'), ['locale' => 'en'])
            ->assertRedirect(route('faqs.index'));

        $this->assertSame('en', $user->fresh()->locale);
        $this->actingAs($user->fresh())->get(route('faqs.index'))->assertSee('Frequently asked questions');
    }

    public function test_guest_can_switch_login_page_language(): void
    {
        $this->from(route('login'))->post(route('locale.update'), ['locale' => 'en'])->assertRedirect(route('login'));

        $this->get(route('login'))->assertSee('Welcome back')->assertSee('Sign in');
    }

    public function test_unknown_locale_is_rejected(): void
    {
        $this->post(route('locale.update'), ['locale' => 'de'])->assertSessionHasErrors('locale');
    }

    public function test_flash_messages_are_translated(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $this->actingAs($user)->post(route('faqs.store'), ['target' => 'general', 'question' => 'Q?', 'answer' => 'A.', 'active' => 1])
            ->assertSessionHas('success', 'Question added.');
    }
}
