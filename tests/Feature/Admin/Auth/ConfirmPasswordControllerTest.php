<?php

namespace Tests\Feature\Admin\Auth;

use App\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\Auth\ConfirmPasswordController
 */
class ConfirmPasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_cant_visit_confirm_password_when_not_authenticated()
    {
        $response = $this->get(route('admin.password.protected'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_can_visit_confirm_password_when_authenticated()
    {
        $admin = factory(Admin::class)->make();

        $response = $this->actingAs($admin, 'admin')->get(route('admin.password.protected'));

        $response->assertRedirect(route('admin.password.confirm'));
    }

    public function test_can_visit_confirm_password_when_authenticated_json()
    {
        $admin = factory(Admin::class)->make();

        $response = $this->actingAs($admin, 'admin')
            ->withHeader('Accept', 'application/json')
            ->get(route('admin.password.protected'));

        $response->assertStatus(423);

        $response->assertJson([
            'message' => 'Password confirmation required.',
        ]);
    }

    public function test_shows_admin_confirm_password_page()
    {
        $admin = factory(Admin::class)->make();

        $response = $this->actingAs($admin, 'admin')->get(route('admin.password.confirm'));

        $response->assertViewIs('admin.auth.passwords.confirm');
    }

    public function test_cant_confirm_with_invalid_password()
    {
        $admin = factory(Admin::class)->create([
            'password' => Hash::make('gJrFhC2B-!Y!4CTk'),
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.password.confirm'))
            ->post(route('admin.password.confirm'), [
                'password' => 'invalid-password',
            ]);

        $response->assertRedirect(route('admin.password.confirm'));
        $this->assertFalse(session()->hasOldInput('password'));
    }

    public function test_can_confirm_with_valid_password()
    {
        $admin = factory(Admin::class)->create([
            'password' => Hash::make('gJrFhC2B-!Y!4CTk'),
        ]);

        $response = $this->actingAs($admin, 'admin')
            // ->from(route('admin.password.protected'))
            ->from(route('admin.password.confirm'))
            ->post(route('admin.password.confirm'), [
                'password' => 'gJrFhC2B-!Y!4CTk',
            ]);

        $response->assertRedirect(route('admin.home'));
    }

    public function test_should_not_reconfirm_password_not_if_timed_out()
    {
        $this->app['session']->put('admin.auth.password_confirmed_at', time());

        $this->app['config']->set('auth.password_timeout', 100);

        $admin = factory(Admin::class)->create([
            'password' => Hash::make('gJrFhC2B-!Y!4CTk'),
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.password.protected'));

        $response->assertStatus(200);
    }

    public function test_should_reconfirm_password_if_timed_out()
    {
        $passwordConfirmedAt = time() - 100;

        $this->app['session']->put('admin.auth.password_confirmed_at', $passwordConfirmedAt);

        $this->app['config']->set('auth.password_timeout', 10);

        $admin = factory(Admin::class)->create([
            'password' => Hash::make('gJrFhC2B-!Y!4CTk'),
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.password.protected'));

        $response->assertRedirect(route('admin.password.confirm'));
    }
}
