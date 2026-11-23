<?php

namespace Tests\Feature;

use App\Models\BlogSubscriber;
use App\Models\HelpRequest;
use App\Models\User;
use App\Support\BulkTranslation;
use App\Support\Notify;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_bell_and_page_show_notifications_and_read_state_is_per_person(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        Notify::everyone('help', 'Yeni yardım talebi: Ayşe', 'TS-12345', '/yardim-talepleri/1');

        $this->actingAs($me)->get(route('notifications.index'))->assertOk()
            ->assertSee('Yeni yardım talebi: Ayşe')->assertSee('TS-12345')->assertSee('data-notifications', false);

        $summary = $this->actingAs($me)->getJson(route('notifications.summary'))->assertOk()->json();
        $this->assertSame(1, $summary['unread']);
        $id = $summary['items'][0]['id'];

        // tıklayınca okundu olur ve ilgili sayfaya gider
        $this->actingAs($me)->get(route('notifications.open', $id))->assertRedirect('/yardim-talepleri/1');
        $this->assertSame(0, $me->unreadNotifications()->count());
        $this->assertSame(1, $other->unreadNotifications()->count()); // diğer kullanıcıda okunmamış kalır

        // başkasının bildirimi açılamaz
        $otherId = $other->notifications()->first()->id;
        $this->actingAs($me)->get(route('notifications.open', $otherId))->assertNotFound();

        $this->actingAs($other)->postJson(route('notifications.read-all'))->assertOk();
        $this->assertSame(0, $other->unreadNotifications()->count());
    }

    public function test_unread_filter_and_clearing_read_ones(): void
    {
        $me = User::factory()->create();
        Notify::everyone('mail', 'Okunan');
        $me->unreadNotifications()->update(['read_at' => now()]);
        Notify::everyone('mail', 'Yeni gelen');

        $this->actingAs($me)->get(route('notifications.index', ['filtre' => 'okunmamis']))
            ->assertViewHas('notifications', fn ($page) => collect($page->items())->pluck('title')->all() === ['Yeni gelen']);
        $this->actingAs($me)->delete(route('notifications.clear-read'));
        $this->assertSame(['Yeni gelen'], $me->notifications()->get()->pluck('data.title')->all());
    }

    public function test_scan_notifies_new_help_requests_and_subscribers_after_the_first_run(): void
    {
        $user = User::factory()->create();
        HelpRequest::create(['name' => 'Eski', 'email' => 'eski@example.com', 'answers' => [], 'locale' => 'en', 'status' => 'new']);

        $this->artisan('notify:scan')->assertSuccessful(); // ilk çalışma: başlangıç noktası, eskiler bildirilmez
        $this->assertSame(0, $user->notifications()->count());

        $help = HelpRequest::create(['name' => 'Ayşe', 'email' => 'ayse@example.com', 'answers' => [], 'locale' => 'tr', 'status' => 'new']);
        BlogSubscriber::create(['email' => 'okur@example.com']);
        $this->artisan('notify:scan')->assertSuccessful();

        $titles = $user->notifications()->get()->pluck('data.title')->all();
        $this->assertContains('Yeni yardım talebi: Ayşe', $titles);
        $this->assertContains('Yeni blog abonesi', $titles);
        $this->assertSame(2, $user->unreadNotifications()->count());

        $this->artisan('notify:scan'); // aynı kayıtlar tekrar bildirilmez
        $this->assertSame(2, $user->notifications()->count());
        $this->assertSame(route('help-requests.show', $help), $user->notifications()->get()->firstWhere('data.kind', 'help')->data['url']);
    }

    public function test_bulk_translation_finish_notifies_the_starter(): void
    {
        $starter = User::factory()->create();
        $other = User::factory()->create();
        \Illuminate\Support\Facades\Cache::forever('bulk-translation:users', [$starter->id]);

        BulkTranslation::recordDone();
        BulkTranslation::recordDone();

        // ilk bitişte sıra boştu: bildirim gitti, sayaçlar sıfırlandı
        $notification = $starter->notifications()->first();
        $this->assertSame('Toplu çeviri bitti', $notification->data['title']);
        $this->assertSame(0, $other->notifications()->count());
    }
}
