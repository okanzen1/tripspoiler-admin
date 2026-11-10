<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_management_cards_and_roles(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('management.index'))
            ->assertOk()
            ->assertSee('Kullanıcılar')
            ->assertSee('Roller');

        $this->actingAs($admin)->get(route('roles.index'))
            ->assertOk()
            ->assertSee('Editör');
    }

    public function test_editor_cannot_open_management(): void
    {
        $editor = User::factory()->create();

        $this->actingAs($editor)->get(route('management.index'))->assertRedirect(route("analytics.index"));
        $this->actingAs($editor)->get(route('roles.index'))->assertRedirect(route("analytics.index"));
    }

    public function test_create_page_renders_wizard(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('users.create'))
            ->assertOk()
            ->assertSee('data-wizard', false)
            ->assertSee('Onay');
    }

    public function test_wizard_reopens_on_first_invalid_step(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->from(route('users.create'))
            ->followingRedirects()
            ->post(route('users.store'), [
                'first_name' => 'Ayşe',
                'last_name' => 'Yılmaz',
                'email' => 'ayse@tripspoiler.com',
                'role' => 'editor',
                'password' => 'kisa',
                'password_confirmation' => 'kisa',
            ])
            ->assertSee('data-start="2"', false);
    }
}
