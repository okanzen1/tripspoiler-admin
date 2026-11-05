<?php

namespace Tests\Feature;

use App\Models\BlogSubscriber;
use App\Models\MailTemplate;
use App\Models\Setting;
use App\Models\User;
use App\Services\Mailbox\InboxUnreadCount;
use App\Services\Mailbox\Mailbox;
use App\Services\Mailbox\OutgoingMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Fakes\FakeMailbox;
use Tests\TestCase;

class MailboxTest extends TestCase
{
    use RefreshDatabase;

    private FakeMailbox $mailbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailbox = new FakeMailbox();
        $this->mailbox->add('INBOX', 1, ['subject' => 'Rezervasyon sorusu']);
        $this->mailbox->add('INBOX', 2, ['subject' => 'Bülten hakkında', 'seen' => true, 'from' => ['name' => null, 'email' => 'abone@example.com']]);
        $this->app->instance(Mailbox::class, $this->mailbox);
        Mail::fake();
    }

    public function test_editor_sees_inbox_with_unread_count_and_subscriber_badge(): void
    {
        BlogSubscriber::create(['email' => 'abone@example.com']);

        $this->actingAs(User::factory()->create())->get(route('mailbox.index'))
            ->assertOk()
            ->assertSeeInOrder(['Bülten hakkında', 'Rezervasyon sorusu'])
            ->assertSee('Abone');

        $this->assertSame(1, InboxUnreadCount::get());
    }

    public function test_search_filters_messages(): void
    {
        $this->actingAs(User::factory()->create())->get(route('mailbox.index', ['q' => 'rezervasyon']))
            ->assertSee('Rezervasyon sorusu')->assertDontSee('Bülten hakkında');
    }

    public function test_show_marks_seen_and_body_is_sandboxed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('mailbox.show', ['folder' => 'INBOX', 'uid' => 1]))
            ->assertOk()->assertSee('ayse@example.com');
        $this->assertTrue($this->mailbox->messages['INBOX'][1]['seen']);

        $this->actingAs($user)->get(route('mailbox.body', ['folder' => 'INBOX', 'uid' => 1]))
            ->assertOk()
            ->assertSee('1 numaralı mesaj')
            ->assertHeader('Content-Security-Policy');
    }

    public function test_unavailable_server_shows_friendly_page(): void
    {
        $this->mailbox->down = true;

        $this->actingAs(User::factory()->create())->get(route('mailbox.index'))
            ->assertOk()->assertSee('E-posta sunucusuna bağlanılamadı');
    }

    public function test_reply_prefills_and_sends_with_signature(): void
    {
        Setting::put('mailbox_signature', 'TripSpoiler Ekibi');
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('mailbox.compose', ['mode' => 'reply', 'folder' => 'INBOX', 'uid' => 1]))
            ->assertOk()->assertSee('Ynt: Rezervasyon sorusu')->assertSee('TripSpoiler Ekibi')->assertSee('ayse@example.com');

        $this->actingAs($user)->post(route('mailbox.send'), [
            'mode' => 'reply',
            'original_folder' => 'INBOX',
            'original_uid' => 1,
            'to' => 'ayse@example.com',
            'subject' => 'Ynt: Rezervasyon sorusu',
            'body' => 'Merhaba Ayşe, yardımcı olalım.',
        ])->assertRedirect(route('mailbox.index', ['folder' => 'INBOX']));

        Mail::assertSent(OutgoingMail::class, fn (OutgoingMail $mail) => $mail->toAddresses === ['ayse@example.com']
            && $mail->inReplyTo === '<1@example.com>');
        $this->assertTrue($this->mailbox->messages['INBOX'][1]['answered']);
    }

    public function test_send_validates_addresses(): void
    {
        $this->actingAs(User::factory()->create())->post(route('mailbox.send'), [
            'mode' => 'new', 'to' => 'gecersiz-adres', 'subject' => 'x', 'body' => 'y',
        ])->assertSessionHasErrors('to');

        Mail::assertNothingSent();
    }

    public function test_delete_moves_to_trash_then_deletes_permanently(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->delete(route('mailbox.destroy'), ['folder' => 'INBOX', 'uids' => [1]]);
        $this->assertArrayHasKey(1, $this->mailbox->messages['INBOX.Trash']);

        $this->actingAs($user)->delete(route('mailbox.destroy'), ['folder' => 'INBOX.Trash', 'uids' => [1]]);
        $this->assertArrayNotHasKey(1, $this->mailbox->messages['INBOX.Trash']);
    }

    public function test_templates_and_signature_can_be_managed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('mailbox.templates.store'), ['title' => 'İade', 'body' => 'İade için partnerle iletişime geç.'])
            ->assertSessionHas('success');
        $template = MailTemplate::first();
        $this->assertSame(0, $template->sort_order);

        $this->actingAs($user)->put(route('mailbox.templates.signature'), ['signature' => 'TripSpoiler'])->assertSessionHas('success');
        $this->assertSame('TripSpoiler', Setting::get('mailbox_signature'));

        $this->actingAs($user)->get(route('mailbox.compose'))->assertSee('İade');
    }
}
