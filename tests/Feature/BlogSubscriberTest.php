<?php

namespace Tests\Feature;

use App\Models\BlogSubscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogSubscriberTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_is_encrypted_and_duplicates_are_blocked(): void
    {
        $subscriber = BlogSubscriber::create(['email' => 'Ayse@Example.com ']);

        $this->assertNotSame('Ayse@Example.com ', \DB::table('blog_subscribers')->value('email'));
        $this->assertTrue($subscriber->is(BlogSubscriber::findByEmail('ayse@example.com')));

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        BlogSubscriber::create(['email' => 'ayse@example.com']);
    }

    public function test_list_search_and_mail_icon(): void
    {
        BlogSubscriber::create(['email' => 'ayse@example.com']);
        BlogSubscriber::create(['email' => 'mehmet@example.com']);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('subscribers.index'))
            ->assertOk()->assertSee('ayse@example.com')->assertSee('mehmet@example.com')
            ->assertSee(route('mailbox.compose', ['to' => 'ayse@example.com']), false);

        $this->actingAs($user)->get(route('subscribers.index', ['search' => 'AYSE@example.com']))
            ->assertSee('ayse@example.com')->assertDontSee('mehmet@example.com');
    }

    public function test_export_and_delete(): void
    {
        $subscriber = BlogSubscriber::create(['email' => '=hack@example.com']);
        $user = User::factory()->create();

        $csv = $this->actingAs($user)->get(route('subscribers.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString("'=hack@example.com", $csv); // formül olarak çalışmasın

        $this->actingAs($user)->delete(route('subscribers.destroy', $subscriber))->assertSessionHas('success');
        $this->assertModelMissing($subscriber);
    }

    public function test_unsubscribe_link_requires_signature_and_confirmation(): void
    {
        $subscriber = BlogSubscriber::create(['email' => 'ayse@example.com']);

        $this->get(route('subscribers.unsubscribe', ['subscriber' => $subscriber->id]))->assertForbidden();

        // Bağlantıyı açmak tek başına çıkarmaz (e-posta tarayıcılarının ön izlemesi)
        $this->get($subscriber->unsubscribeUrl())->assertOk()->assertSee('Abonelikten çıkılsın mı?');
        $this->assertTrue($subscriber->fresh()->isActive());

        $confirmUrl = \URL::signedRoute('subscribers.unsubscribe.confirm', ['subscriber' => $subscriber->id]);
        $this->post($confirmUrl)->assertOk()->assertSee('Aboneliğin iptal edildi');
        $this->assertFalse($subscriber->fresh()->isActive());
    }
}
