<?php

namespace Tests\Feature;

use App\Models\AffiliateClick;
use App\Models\Partner;
use App\Models\User;
use App\Services\Analytics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PartnerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('media');
        $this->admin = User::factory()->admin()->create();
    }

    private function click(string $partner): void
    {
        AffiliateClick::create([
            'visitor_id' => 'v', 'session_id' => 's', 'partner' => $partner,
            'path' => '/x', 'target_url' => 'https://example.com', 'created_at' => now(),
        ]);
    }

    public function test_editor_cannot_manage_partners(): void
    {
        $editor = User::factory()->create();

        $this->actingAs($editor)->get(route('partners.index'))->assertRedirect(route("analytics.index"));
        $this->actingAs($editor)->post(route('partners.store'), ['name' => 'X'])->assertForbidden();
    }

    public function test_admin_can_create_partner_with_logo_and_auto_code(): void
    {
        $this->actingAs($this->admin)->post(route('partners.store'), [
            'name' => 'Get Your Guide',
            'website' => 'https://www.getyourguide.com',
            'logo' => UploadedFile::fake()->image('logo.png', 1200, 600),
            'active' => '1',
        ])->assertRedirect(route('partners.index'));

        $partner = Partner::first();
        $this->assertSame('getyourguide', $partner->slug);
        $this->assertTrue($partner->active);

        // Logo WebP'ye çevrilip 400 px sınırına küçültülür
        Storage::disk('media')->assertExists($partner->logo_path);
        $this->assertStringEndsWith('.webp', $partner->logo_path);
        [$width, $height] = getimagesizefromstring(Storage::disk('media')->get($partner->logo_path));
        $this->assertSame([400, 200], [$width, $height]);
    }

    public function test_create_validates_input(): void
    {
        Partner::create(['name' => 'Viator', 'slug' => 'viator']);

        $this->actingAs($this->admin)->post(route('partners.store'), [
            'name' => 'Viator',
            'website' => 'viator.com',
            'logo' => UploadedFile::fake()->create('logo.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors(['slug', 'website', 'logo']);
    }

    public function test_logo_can_be_replaced_and_removed(): void
    {
        $partner = Partner::create(['name' => 'Viator', 'slug' => 'viator']);
        $update = fn (array $extra) => $this->actingAs($this->admin)
            ->put(route('partners.update', $partner), ['name' => 'Viator', 'active' => '1'] + $extra);

        $update(['logo' => UploadedFile::fake()->image('a.png')]);
        $first = $partner->fresh()->logo_path;
        Storage::disk('media')->assertExists($first);

        $update(['logo' => UploadedFile::fake()->image('b.png')]);
        $second = $partner->fresh()->logo_path;
        Storage::disk('media')->assertMissing($first); // eski dosya silinir
        Storage::disk('media')->assertExists($second);

        $update(['remove_logo' => '1']);
        $this->assertNull($partner->fresh()->logo_path);
        Storage::disk('media')->assertMissing($second);
    }

    public function test_code_cannot_change_after_creation(): void
    {
        $partner = Partner::create(['name' => 'Viator', 'slug' => 'viator']);

        $this->actingAs($this->admin)->put(route('partners.update', $partner), [
            'name' => 'Viator Partners', 'slug' => 'baska-kod', 'active' => '1',
        ])->assertRedirect(route('partners.index'));

        $partner->refresh();
        $this->assertSame('Viator Partners', $partner->name);
        $this->assertSame('viator', $partner->slug);
    }

    public function test_partner_with_clicks_cannot_be_deleted(): void
    {
        $used = Partner::create(['name' => 'Viator', 'slug' => 'viator']);
        $unused = Partner::create(['name' => 'Klook', 'slug' => 'klook']);
        $this->click('viator');

        $this->actingAs($this->admin)->delete(route('partners.destroy', $used))->assertSessionHas('error');
        $this->assertModelExists($used);

        $this->actingAs($this->admin)->delete(route('partners.destroy', $unused))->assertRedirect(route('partners.index'));
        $this->assertModelMissing($unused);
    }

    public function test_index_lists_partners_with_click_counts(): void
    {
        $partner = Partner::create(['name' => 'Tiqets', 'slug' => 'tiqets']);
        $this->click('tiqets');
        $this->click('tiqets');

        $this->actingAs($this->admin)->get(route('partners.index'))
            ->assertOk()->assertSee('Tiqets')->assertSee('Tıklama (30 gün)');

        $this->actingAs($this->admin)->patchJson(route('partners.toggle', $partner))->assertJson(['active' => true]);
    }

    public function test_analytics_uses_partner_names(): void
    {
        Partner::create(['name' => 'GetYourGuide', 'slug' => 'getyourguide']);
        $this->click('getyourguide');

        $this->assertSame('GetYourGuide', (new Analytics(7))->partners()->first()->label);
    }
}
