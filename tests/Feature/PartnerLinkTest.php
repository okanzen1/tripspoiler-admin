<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\Partner;
use App\Models\PartnerLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_manages_partner_links(): void
    {
        $editor = User::factory()->create();
        $country = Country::create(['code' => 'TR', 'name' => 'Türkiye', 'slug' => 'turkiye', 'active' => true]);
        $city = City::create(['country_id' => $country->id, 'name' => 'Istanbul', 'slug' => 'istanbul', 'active' => true]);
        $partner = Partner::create(['name' => 'Megapass', 'slug' => 'megapass', 'website' => 'https://megapass.com', 'active' => true]);

        $this->actingAs($editor)->get(route('partner-links.index'))->assertOk()->assertSee('Henüz bağlantı yok');
        $this->actingAs($editor)->get(route('partner-links.create'))->assertOk();

        // geçersiz bağlantı reddedilir
        $this->actingAs($editor)->post(route('partner-links.store'), ['partner_id' => $partner->id, 'url' => 'megapass'])->assertSessionHasErrors('url');

        $this->actingAs($editor)->post(route('partner-links.store'), [
            'partner_id' => $partner->id, 'city_id' => $city->id, 'url' => 'https://megapass.com/istanbul?ref=ts', 'count_label' => ' 200+ ', 'active' => '1',
        ])->assertRedirect(route('partner-links.index'));
        $link = PartnerLink::first();
        $this->assertSame('200+', $link->count_label);
        $this->assertTrue($link->active);

        // bütün şehirler + pasif
        $this->actingAs($editor)->put(route('partner-links.update', $link), [
            'partner_id' => $partner->id, 'city_id' => '', 'url' => 'https://megapass.com/all', 'active' => '0',
        ])->assertRedirect(route('partner-links.index'));
        $link->refresh();
        $this->assertNull($link->city_id);
        $this->assertFalse($link->active);

        $this->actingAs($editor)->patch(route('partner-links.toggle', $link))->assertRedirect();
        $this->assertTrue($link->fresh()->active);
        $this->actingAs($editor)->get(route('partner-links.index'))->assertOk()->assertSee('Bütün şehirler');

        $this->actingAs($editor)->delete(route('partner-links.destroy', $link))->assertRedirect();
        $this->assertSame(0, PartnerLink::count());
    }

    public function test_link_can_target_one_tab_and_have_an_image(): void
    {
        \Illuminate\Support\Facades\Storage::fake('media');
        $editor = User::factory()->create();
        $partner = Partner::create(['name' => 'Megapass', 'slug' => 'megapass', 'active' => true]);

        $this->actingAs($editor)->post(route('partner-links.store'), [
            'partner_id' => $partner->id, 'type' => 'pass', 'url' => 'https://megapass.com/istanbul-pass', 'active' => '1',
            'image' => \Illuminate\Http\UploadedFile::fake()->image('card.jpg', 1200, 800),
        ])->assertSessionHasNoErrors()->assertRedirect(route('partner-links.index'));
        $link = PartnerLink::first();
        $this->assertSame('pass', $link->type);
        \Illuminate\Support\Facades\Storage::disk('media')->assertExists($link->image_path);

        // geçersiz sekme reddedilir; resim kaldırılabilir
        $this->actingAs($editor)->put(route('partner-links.update', $link), ['partner_id' => $partner->id, 'type' => 'hotel', 'url' => 'https://megapass.com'])->assertSessionHasErrors('type');
        $old = $link->image_path;
        $this->actingAs($editor)->put(route('partner-links.update', $link), ['partner_id' => $partner->id, 'type' => '', 'url' => 'https://megapass.com', 'remove_image' => '1', 'active' => '1'])->assertSessionHasNoErrors();
        $link->refresh();
        $this->assertNull($link->type);
        $this->assertNull($link->image_path);
        \Illuminate\Support\Facades\Storage::disk('media')->assertMissing($old);
    }
}
