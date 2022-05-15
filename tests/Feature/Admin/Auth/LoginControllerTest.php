<?php

namespace Tests\Feature\Admin\Auth;

use App\Models\Admin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\Auth\LoginController
 */
final class LoginControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testCantVisitLoginWhenAuthenticated()
    {
        $admin = Admin::factory()->make();

        $response = $this->actingAs($admin, 'admin')->get(route('admin.login'));

        $response->assertRedirect(route('admin.home'));
    }

    public function testCanVisitLoginIfNotAuthenticated()
    {
        $response = $this->get(route('admin.login'));

        $response->assertSuccessful();
        $response->assertViewIs('admin.auth.login');
    }

    public function testCantLoginWithInvalidEmail()
    {
        $admin = Admin::factory()->create([
            'password' => Hash::make('gJrFhC2B-!Y!4CTk'),
        ]);

        $response = $this->from(route('admin.login'))->post(route('admin.login'), [
            'email' => 'unknown@example.com',
            'password' => $admin->password,
        ]);

        $response->assertRedirect(route('admin.login'));
        $response->assertSessionHasErrors('email');
        static::assertTrue(session()->hasOldInput('email'));
        static::assertFalse(session()->hasOldInput('password'));
        $this->assertGuest('admin');
    }

    public function testCantLoginWithInvalidPassword()
    {
        $admin = Admin::factory()->create([
            'password' => Hash::make('gJrFhC2B-!Y!4CTk'),
        ]);

        $response = $this->from(route('admin.login'))->post(route('admin.login'), [
            'email' => $admin->email,
            'password' => 'invalid-password',
        ]);

        $response->assertRedirect(route('admin.login'));
        $response->assertSessionHasErrors('email');
        static::assertTrue(session()->hasOldInput('email'));
        static::assertFalse(session()->hasOldInput('password'));
        $this->assertGuest('admin');
    }

    public function testCantMakeMoreThanFiveFailedLoginAttemptsAMinute()
    {
        $admin = Admin::factory()->create([
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
        static::assertStringContainsString(
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
        static::assertTrue(session()->hasOldInput('email'));
        static::assertFalse(session()->hasOldInput('password'));
        $this->assertGuest('admin');
    }

    public function testCanLoginWithCorrectCredentials()
    {
        $password = 'gJrFhC2B-!Y!4CTk';

        $admin = Admin::factory()->create([
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
     * @group passing
     *
     * @throws \Exception
     */
    public function testCanBeRemembered()
    {
        $password = 'gJrFhC2B-!Y!4CTk';

        $admin = Admin::factory()->create([
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

    public function testCantLogoutIfNotAuthenticated()
    {
        $response = $this->post(route('admin.logout'));

        $response->assertRedirect(route('admin.home'));
        $this->assertGuest('admin');
    }

    public function testCanLogoutIfAuthenticated()
    {
        $this->be(Admin::factory()->create());

        $response = $this->post(route('admin.logout'));

        $response->assertRedirect(route('admin.home'));
        $this->assertGuest('admin');
    }
}
