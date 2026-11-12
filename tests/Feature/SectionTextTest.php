<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionTextTest extends TestCase
{
    use RefreshDatabase;

    public function test_popular_section_saves_overlay_without_text_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('section-texts.edit', 'popular'))
            ->assertOk()->assertSee(__('Resmin karartması'))->assertDontSee(__('Yazılar'));

        $this->actingAs($admin)->put(route('section-texts.update', 'popular'), ['overlay' => 'strong'])
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame('strong', Setting::get('popular_overlay'));
        $this->assertDatabaseMissing('section_texts', ['key' => 'popular']);
    }
}
