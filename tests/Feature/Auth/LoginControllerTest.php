<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Auth\LoginController
 */
class LoginControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_cant_visit_login_when_authenticated()
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)->get(route('login'));

        $response->assertRedirect(route('home'));
    }

    public function test_can_visit_login_if_not_authenticated()
    {
        $response = $this->get(route('login'));

        $response->assertSuccessful();
        $response->assertViewIs('auth.login');
    }

    public function test_cant_login_with_invalid_email()
    {
        $user = User::factory()->create([
            'password' => Hash::make('gJrFhC2B-!Y!4CTk'),
        ]);

        $response = $this->from(route('login'))->post(route('login'), [
            'email' => 'unknown@example.com',
            'password' => $user->password,
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertGuest();
    }

    public function test_cant_login_with_invalid_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('gJrFhC2B-!Y!4CTk'),
        ]);

        $response = $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'invalid-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertGuest();
    }

    public function test_cant_make_more_than_five_failed_login_attempts_a_minute()
    {
        $user = User::factory()->create([
            'password' => Hash::make('gJrFhC2B-!Y!4CTk'),
        ]);

        foreach (range(0, 5) as $_) {
            $response = $this->from(route('login'))->post(route('login'), [
                'email' => $user->email,
                'password' => 'invalid-password',
            ]);
        }

        $response->assertRedirect(route('login'));
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
        $this->assertGuest();
    }

    public function test_can_login_with_correct_credentials()
    {
        $password = 'gJrFhC2B-!Y!4CTk';

        $user = User::factory()->create([
            'password' => Hash::make($password),
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
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

        $user = User::factory()->create([
            'password' => Hash::make($password),
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => $password,
            'remember' => 'on',
        ]);

        $user = $user->fresh();

        $response->assertRedirect(route('home'));
        $response->assertCookie(Auth::guard()->getRecallerName(), vsprintf('%s|%s|%s', [
            $user->id,
            $user->getRememberToken(),
            $user->password,
        ]));
        $this->assertAuthenticatedAs($user);
    }

    public function test_cant_logout_if_not_authenticated()
    {
        $response = $this->post(route('logout'));

        $response->assertRedirect(url('/'));
        $this->assertGuest();
    }

    public function test_can_logout_if_authenticated()
    {
        $this->be(User::factory()->create());

        $response = $this->post(route('logout'));

        $response->assertRedirect(url('/'));
        $this->assertGuest();
    }
}
