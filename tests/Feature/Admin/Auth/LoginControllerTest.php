<?php

namespace Tests\Feature\Admin\Auth;

use App\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\Auth\LoginController
 */
class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_cant_visit_login_when_authenticated()
    {
        $admin = factory(Admin::class)->make();

        $response = $this->actingAs($admin, 'admin')->get(route('admin.login'));

        $response->assertRedirect(route('admin.home'));
    }

    public function test_can_visit_login_if_not_authenticated()
    {
        $response = $this->get(route('admin.login'));

        $response->assertSuccessful();
        $response->assertViewIs('admin.auth.login');
    }

    public function test_cant_login_with_invalid_email()
    {
        $admin = factory(Admin::class)->create([
            'password' => Hash::make('gJrFhC2B-!Y!4CTk'),
        ]);

        $response = $this->from(route('admin.login'))->post(route('admin.login'), [
            'email' => 'unknown@example.com',
            'password' => $admin->password,
        ]);

        $response->assertRedirect(route('admin.login'));
        $response->assertSessionHasErrors('email');
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertGuest('admin');
    }

    public function test_cant_login_with_invalid_password()
    {
        $admin = factory(Admin::class)->create([
            'password' => Hash::make('gJrFhC2B-!Y!4CTk'),
        ]);

        $response = $this->from(route('admin.login'))->post(route('admin.login'), [
            'email' => $admin->email,
            'password' => 'invalid-password',
        ]);

        $response->assertRedirect(route('admin.login'));
        $response->assertSessionHasErrors('email');
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertGuest('admin');
    }

    public function test_cant_make_more_than_five_failed_login_attempts_a_minute()
    {
        $admin = factory(Admin::class)->create([
            'password' => Hash::make('gJrFhC2B-!Y!4CTk'),
        ]);

        foreach (range(0, 5) as $_) {
            $response = $this->from(route('admin.login'))->post(route('admin.login'), [
                'email' => $admin->email,
                'password' => 'invalid-password',
            ]);
        }

        $response->assertRedirect(route('admin.login'));
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Too many login attempts.',
            collect(
                $response
                    ->baseResponse
                    ->getSession()
                    ->get('errors')
                    ->getBag('default')
                    ->get('email')
            )->first()
        );
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertGuest('admin');
    }

    public function test_can_login_with_correct_credentials()
    {
        $password = 'gJrFhC2B-!Y!4CTk';

        $admin = factory(Admin::class)->create([
            'password' => Hash::make($password),
        ]);

        $response = $this->post(route('admin.login'), [
            'email' => $admin->email,
            'password' => $password,
        ]);

        $response->assertRedirect(route('admin.home'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    /**
     * @test
     * @group passing
     *
     * @throws \Exception
     */
    public function test_can_be_remembered()
    {
        $password = 'gJrFhC2B-!Y!4CTk';

        $admin = factory(Admin::class)->create([
            'password' => Hash::make($password),
        ]);

        $response = $this->post(route('admin.login'), [
            'email' => $admin->email,
            'password' => $password,
            'remember' => 'on',
        ]);

        $admin = $admin->fresh();

        $response->assertRedirect(route('admin.home'));
        $response->assertCookie(Auth::guard('admin')->getRecallerName(), vsprintf('%s|%s|%s', [
            $admin->id,
            $admin->getRememberToken(),
            $admin->password,
        ]));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_cant_logout_if_not_authenticated()
    {
        $response = $this->post(route('admin.logout'));

        $response->assertRedirect(route('admin.home'));
        $this->assertGuest('admin');
    }

    public function test_can_logout_if_authenticated()
    {
        $this->be(factory(Admin::class)->create());

        $response = $this->post(route('admin.logout'));

        $response->assertRedirect(route('admin.home'));
        $this->assertGuest('admin');
    }
}
