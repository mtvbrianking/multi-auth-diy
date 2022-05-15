<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Auth\LoginController
 *
 * @internal
 * @coversNothing
 */
final class LoginControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testCantVisitLoginWhenAuthenticated()
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)->get(route('login'));

        $response->assertRedirect(route('home'));
    }

    public function testCanVisitLoginIfNotAuthenticated()
    {
        $response = $this->get(route('login'));

        $response->assertSuccessful();
        $response->assertViewIs('auth.login');
    }

    public function testCantLoginWithInvalidEmail()
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
        static::assertTrue(session()->hasOldInput('email'));
        static::assertFalse(session()->hasOldInput('password'));
        $this->assertGuest();
    }

    public function testCantLoginWithInvalidPassword()
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
        static::assertTrue(session()->hasOldInput('email'));
        static::assertFalse(session()->hasOldInput('password'));
        $this->assertGuest();
    }

    public function testCantMakeMoreThanFiveFailedLoginAttemptsAMinute()
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
        $this->assertGuest();
    }

    public function testCanLoginWithCorrectCredentials()
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
     * @group passing
     *
     * @throws \Exception
     */
    public function testCanBeRemembered()
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

    public function testCantLogoutIfNotAuthenticated()
    {
        $response = $this->post(route('logout'));

        $response->assertRedirect(url('/'));
        $this->assertGuest();
    }

    public function testCanLogoutIfAuthenticated()
    {
        $this->be(User::factory()->create());

        $response = $this->post(route('logout'));

        $response->assertRedirect(url('/'));
        $this->assertGuest();
    }
}
