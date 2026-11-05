<?php

namespace Tests\Feature;

use App\Jobs\SendBulkEmail;
use App\Models\BlogSubscriber;
use App\Models\BulkEmail;
use App\Models\User;
use App\Services\Mailbox\OutgoingMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BulkEmailTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(); // editör de gönderebilir
        BlogSubscriber::create(['email' => 'a@example.com']);
        BlogSubscriber::create(['email' => 'b@example.com']);
        BlogSubscriber::create(['email' => 'cikmis@example.com', 'unsubscribed_at' => now()]);
    }

    public function test_bulk_queues_one_job_per_recipient_and_skips_duplicates(): void
    {
        Queue::fake();

        $this->actingAs($this->user)->post(route('mailbox.bulk.store'), [
            'subscriber_ids' => BlogSubscriber::active()->pluck('id')->all(),
            'extra_emails' => "A@example.com, yeni@example.com\nyeni@example.com",
            'subject' => 'Kasım turları',
            'body' => 'Merhaba!',
            'confirm' => '1',
        ])->assertRedirect(route('mailbox.index'))->assertSessionHas('success', fn ($m) => str_contains($m, '3 kişiye'));

        // 2 aktif abone + 1 yeni adres (A@ zaten abone, yeni@ iki kez yazılmış)
        Queue::assertPushed(SendBulkEmail::class, 3);
        $this->assertSame(3, BulkEmail::first()->recipient_count);
    }

    public function test_unchecked_subscribers_are_left_out(): void
    {
        Queue::fake();
        $keep = BlogSubscriber::findByEmail('a@example.com');
        $gone = BlogSubscriber::findByEmail('cikmis@example.com'); // abonelikten çıkmış: id gönderilse bile atlanır

        $this->actingAs($this->user)->post(route('mailbox.bulk.store'), [
            'subscriber_ids' => [$keep->id, $gone->id], // b@ listeden çıkarıldı
            'subject' => 'Konu',
            'body' => 'Metin',
            'confirm' => '1',
        ])->assertSessionHas('success', fn ($m) => str_contains($m, '1 kişiye'));

        Queue::assertPushed(SendBulkEmail::class, 1);
        Queue::assertPushed(SendBulkEmail::class, fn (SendBulkEmail $job) => $job->subscriberId === $keep->id);
    }

    public function test_bulk_requires_recipients_confirmation_and_valid_addresses(): void
    {
        Queue::fake();

        $this->actingAs($this->user)->post(route('mailbox.bulk.store'), [
            'subscriber_ids' => [], 'extra_emails' => '', 'subject' => 'x', 'body' => 'y', 'confirm' => '1',
        ])->assertSessionHas('error');

        $this->actingAs($this->user)->post(route('mailbox.bulk.store'), [
            'subscriber_ids' => BlogSubscriber::active()->pluck('id')->all(), 'subject' => 'x', 'body' => 'y',
        ])->assertSessionHasErrors('confirm');

        $this->actingAs($this->user)->post(route('mailbox.bulk.store'), [
            'extra_emails' => 'bozuk@', 'subject' => 'x', 'body' => 'y', 'confirm' => '1',
        ])->assertSessionHasErrors('extra_emails');

        Queue::assertNothingPushed();
    }

    public function test_job_adds_unsubscribe_link_for_subscribers_and_skips_unsubscribed(): void
    {
        Mail::fake();
        $active = BlogSubscriber::findByEmail('a@example.com');
        $gone = BlogSubscriber::findByEmail('cikmis@example.com');

        (new SendBulkEmail('Konu', 'Metin', subscriberId: $active->id))->handle(app(\App\Services\Mailbox\BulkEmailComposer::class));
        (new SendBulkEmail('Konu', 'Metin', subscriberId: $gone->id))->handle(app(\App\Services\Mailbox\BulkEmailComposer::class));
        (new SendBulkEmail('Konu', 'Metin', address: 'dis@example.com'))->handle(app(\App\Services\Mailbox\BulkEmailComposer::class));

        Mail::assertSentCount(2);
        Mail::assertSent(OutgoingMail::class, fn (OutgoingMail $m) => $m->toAddresses === ['a@example.com']
            && str_contains($m->bodyText, '/abonelik/') && $m->listUnsubscribe !== null);
        Mail::assertSent(OutgoingMail::class, fn (OutgoingMail $m) => $m->toAddresses === ['dis@example.com'] && $m->listUnsubscribe === null);
    }

    public function test_bulk_page_shows_active_subscriber_count(): void
    {
        $this->actingAs($this->user)->get(route('mailbox.bulk.create'))
            ->assertOk()->assertSee('a@example.com')->assertSee('b@example.com')->assertDontSee('cikmis@example.com')->assertSee('data-wizard', false);
    }
}
