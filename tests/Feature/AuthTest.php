<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_login_page_renders(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('E-posta');
    }

    public function test_user_can_login_with_email(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);

        $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'secret123'])
            ->assertRedirect(route('analytics.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);

        $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'yanlis'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_and_editor_can_see_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/')->assertOk()->assertSee('Admin');
        $this->actingAs(User::factory()->create())->get('/')->assertOk()->assertSee('Editör');
    }

    public function test_admin_only_routes_reject_editor(): void
    {
        Route::middleware(['web', 'auth', 'role:admin'])->get('/_admin-only', fn () => 'ok');

        $this->actingAs(User::factory()->create())->get('/_admin-only')->assertRedirect(route("analytics.index"));
        $this->actingAs(User::factory()->admin()->create())->get('/_admin-only')->assertOk();
    }
    public function test_login_is_always_remembered(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);

        $response = $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'secret123']);

        $response->assertCookie(\Illuminate\Support\Facades\Auth::guard()->getRecallerName());
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_expired_form_goes_back_with_input_instead_of_error_page(): void
    {
        // Testlerde CSRF kontrolü çalışmadığı için süresi dolmuş anahtar hatası elle fırlatılır
        Route::post('/_test/expired', fn () => throw new \Illuminate\Session\TokenMismatchException())->middleware('web');

        $this->actingAs(User::factory()->create())->from(route('faqs.create'))
            ->post('/_test/expired', ['question' => 'Q?'])
            ->assertRedirect(route('faqs.create'))
            ->assertSessionHas('error')
            ->assertSessionHasInput('question', 'Q?');
    }
}
