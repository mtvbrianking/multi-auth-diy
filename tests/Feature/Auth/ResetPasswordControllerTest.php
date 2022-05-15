<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Auth\ResetPasswordController
 */
final class ResetPasswordControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testCantVisitResetPasswordWhenAuthenticated()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('password.reset', $this->getResetToken($user)));

        $response->assertRedirect(route('home'));
    }

    public function testCanVisitResetPasswordWhenUnauthenticated()
    {
        $user = User::factory()->create();

        $reset_token = $this->getResetToken($user);

        $response = $this->get(route('password.reset', $reset_token));

        $response->assertSuccessful();
        $response->assertViewIs('auth.passwords.reset');
        $response->assertViewHas('token', $reset_token);
    }

    public function testCantResetPasswordWithInvalidToken()
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->from(route('password.reset', 'invalid_token'))->post(route('password.update'), [
            'token' => 'invalid_token',
            'email' => $user->email,
            'password' => 'new-awesome-password',
            'password_confirmation' => 'new-awesome-password',
        ]);

        $response->assertRedirect(route('password.reset', 'invalid_token'));
        static::assertSame($user->email, $user->fresh()->email);
        static::assertTrue(Hash::check('old-password', $user->fresh()->password));
        $this->assertGuest();
    }

    public function testCantResetPasswordWithInvalidEmail()
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $reset_token = $this->getResetToken($user);

        $response = $this->from(route('password.reset', $reset_token))->post(route('password.update'), [
            'token' => $reset_token,
            'email' => '',
            'password' => 'new-awesome-password',
            'password_confirmation' => 'new-awesome-password',
        ]);

        $response->assertRedirect(route('password.reset', $reset_token));
        $response->assertSessionHasErrors('email');
        static::assertFalse(session()->hasOldInput('password'));
        static::assertSame($user->email, $user->fresh()->email);
        static::assertTrue(Hash::check('old-password', $user->fresh()->password));
        $this->assertGuest();
    }

    public function testCantResetPasswordWithInvalidPassword()
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->from(route('password.reset', $token = $this->getResetToken($user)))->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertRedirect(route('password.reset', $token));
        $response->assertSessionHasErrors('password');
        static::assertTrue(session()->hasOldInput('email'));
        static::assertFalse(session()->hasOldInput('password'));
        static::assertSame($user->email, $user->fresh()->email);
        static::assertTrue(Hash::check('old-password', $user->fresh()->password));
        $this->assertGuest();
    }

    public function testCanResetPasswordWithValidToken()
    {
        Event::fake();
        $user = User::factory()->create();

        $response = $this->post(route('password.update'), [
            'token' => $this->getResetToken($user),
            'email' => $user->email,
            'password' => 'new-awesome-password',
            'password_confirmation' => 'new-awesome-password',
        ]);

        $response->assertRedirect(route('home'));
        static::assertSame($user->email, $user->fresh()->email);
        static::assertTrue(Hash::check('new-awesome-password', $user->fresh()->password));
        $this->assertAuthenticatedAs($user);
        Event::assertDispatched(PasswordReset::class, function ($e) use ($user) {
            return $e->user->id === $user->id;
        });
    }

    protected function getResetToken($user)
    {
        return Password::broker()->createToken($user);
    }
}
