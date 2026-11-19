<?php

namespace Tests\Feature;

use App\Models\HelpRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Yardım talepleri: kişisel veri şifreli saklanır; panelde listelenir, açılır, durumu değişir, silinir */
class HelpRequestTest extends TestCase
{
    use RefreshDatabase;

    private function make(array $answers = []): HelpRequest
    {
        return HelpRequest::create([
            'name' => 'Ayşe Yılmaz', 'email' => 'Ayse@Example.com', 'locale' => 'tr',
            'answers' => $answers + ['city_names' => ['Istanbul'], 'days' => 3, 'group' => 'family', 'needs' => ['activities', 'stay'], 'stay_budget' => 'mid', 'note' => 'Çocukla Ayasofya mı Topkapı mı?'],
        ]);
    }

    public function test_personal_data_is_stored_encrypted(): void
    {
        $item = $this->make();
        $row = DB::table('help_requests')->find($item->id);

        foreach (['name', 'email', 'answers'] as $column) {
            $this->assertStringNotContainsString('Ayşe', $row->$column);
            $this->assertStringNotContainsString('example.com', strtolower($row->$column));
            $this->assertStringNotContainsString('Istanbul', $row->$column);
        }
        $this->assertSame(64, strlen($row->email_hash));
        $this->assertSame('Ayşe Yılmaz', $item->fresh()->name);
        $this->assertSame('Istanbul', $item->fresh()->answers['city_names'][0]);
    }

    public function test_panel_lists_shows_updates_and_deletes(): void
    {
        $item = $this->make();
        $older = $this->make();
        $admin = User::factory()->create();

        $this->actingAs($admin)->get(route('help-requests.index'))->assertOk()
            ->assertSee('Ayşe Yılmaz')->assertSee('Istanbul')->assertSee('Aile');

        $this->get(route('help-requests.show', $item))->assertOk()
            ->assertSee('Çocukla Ayasofya mı Topkapı mı?')->assertSee('Konaklama (semt)')->assertSee('Orta')
            ->assertSee('Aynı kişinin diğer talepleri')
            ->assertSee(route('mailbox.compose', ['to' => 'Ayse@Example.com']), false);

        $this->patch(route('help-requests.update', $item), ['status' => 'answered'])->assertRedirect();
        $this->assertSame('answered', $item->fresh()->status);
        $this->assertNotNull($item->fresh()->answered_at);

        // talep numarası: TS- + 5 rakam, panelde numarayla bulunur
        $this->assertMatchesRegularExpression('/^TS-\\d{5}$/', $item->reference);
        $this->get(route('help-requests.index', ['q' => substr($item->reference, 3)]))->assertOk()->assertSee($item->reference);
        $this->get(route('help-requests.index', ['q' => 'TS-00000']))->assertOk()->assertSee('Bu numarayla talep yok');

        $this->delete(route('help-requests.destroy', $older))->assertRedirect(route('help-requests.index'));
        $this->assertNull(HelpRequest::find($older->id));
    }

    public function test_old_requests_are_pruned_after_a_year(): void
    {
        $old = $this->make();
        $old->forceFill(['created_at' => now()->subMonths(13)])->saveQuietly();
        $new = $this->make();

        $this->artisan('model:prune', ['--model' => [HelpRequest::class]])->assertSuccessful();

        $this->assertNull(HelpRequest::find($old->id));
        $this->assertNotNull(HelpRequest::find($new->id));
    }
}
