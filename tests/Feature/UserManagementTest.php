<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_editor_cannot_access_user_management(): void
    {
        $this->actingAs(User::factory()->create())->get(route('users.index'))->assertForbidden();
    }

    public function test_admin_can_list_and_search_users(): void
    {
        User::factory()->create(['first_name' => 'Ayşe', 'last_name' => 'Yılmaz']);
        User::factory()->create(['first_name' => 'Mehmet', 'last_name' => 'Kaya']);

        $this->actingAs($this->admin)->get(route('users.index', ['search' => 'Ayşe']))
            ->assertOk()
            ->assertSee('Ayşe Yılmaz')
            ->assertDontSee('Mehmet Kaya');
    }

    public function test_admin_can_create_user(): void
    {
        $this->actingAs($this->admin)->post(route('users.store'), [
            'first_name' => 'Ayşe',
            'last_name' => 'Yılmaz',
            'email' => 'ayse@tripspoiler.com',
            'role' => 'editor',
            'password' => 'sifre1234',
            'password_confirmation' => 'sifre1234',
        ])->assertRedirect(route('users.index'));

        $user = User::where('email', 'ayse@tripspoiler.com')->first();
        $this->assertSame(Role::Editor, $user->role);
        $this->assertTrue(Hash::check('sifre1234', $user->password));
    }

    public function test_create_validates_input(): void
    {
        $this->actingAs($this->admin)->post(route('users.store'), [
            'email' => $this->admin->email,
            'role' => 'superuser',
            'password' => 'kisa',
            'password_confirmation' => 'farkli',
        ])->assertSessionHasErrors(['first_name', 'last_name', 'email', 'role', 'password']);
    }

    public function test_update_keeps_password_when_blank(): void
    {
        $user = User::factory()->create(['password' => 'eskisifre1']);

        $this->actingAs($this->admin)->put(route('users.update', $user), [
            'first_name' => 'Yeni',
            'last_name' => 'İsim',
            'email' => $user->email,
            'role' => 'admin',
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('users.index'));

        $user->refresh();
        $this->assertSame('Yeni', $user->first_name);
        $this->assertSame(Role::Admin, $user->role);
        $this->assertTrue(Hash::check('eskisifre1', $user->password));
    }

    public function test_admin_cannot_change_own_role(): void
    {
        $this->actingAs($this->admin)->put(route('users.update', $this->admin), [
            'first_name' => $this->admin->first_name,
            'last_name' => $this->admin->last_name,
            'email' => $this->admin->email,
            'role' => 'editor',
        ])->assertSessionHas('error');

        $this->assertSame(Role::Admin, $this->admin->fresh()->role);
    }

    public function test_admin_can_delete_others_but_not_self(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin)->delete(route('users.destroy', $user))->assertRedirect(route('users.index'));
        $this->assertModelMissing($user);

        $this->actingAs($this->admin)->delete(route('users.destroy', $this->admin))->assertSessionHas('error');
        $this->assertModelExists($this->admin);
    }

    public function test_avatar_is_cropped_square_and_shown(): void
    {
        Storage::fake('media');

        $this->actingAs($this->admin)->post(route('users.store'), [
            'first_name' => 'Ayşe',
            'last_name' => 'Yılmaz',
            'email' => 'ayse@tripspoiler.com',
            'role' => 'editor',
            'password' => 'sifre1234',
            'password_confirmation' => 'sifre1234',
            'avatar' => UploadedFile::fake()->image('ben.jpg', 800, 500),
        ])->assertRedirect(route('users.index'));

        $user = User::where('email', 'ayse@tripspoiler.com')->first();
        [$width, $height] = getimagesizefromstring(Storage::disk('media')->get($user->avatar_path));
        $this->assertSame([256, 256], [$width, $height]);

        $this->actingAs($this->admin)->get(route('users.index'))->assertSee($user->avatarUrl());

        // Kullanıcı silinince resmi de silinir
        $this->actingAs($this->admin)->delete(route('users.destroy', $user));
        Storage::disk('media')->assertMissing($user->avatar_path);
    }
}
