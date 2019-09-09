<?php

namespace Tests\Feature\Auth;

use App\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @see \App\Http\Controllers\Auth\ResetPasswordController
 */
class ResetPasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function getResetToken($user)
    {
        return Password::broker()->createToken($user);
    }

    public function test_cant_visit_reset_password_when_authenticated()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->get(route('password.reset', $this->getResetToken($user)));

        $response->assertRedirect(route('home'));
    }

    public function test_can_visit_reset_password_when_unauthenticated()
    {
        $user = factory(User::class)->create();

        $reset_token = $this->getResetToken($user);

        $response = $this->get(route('password.reset', $reset_token));

        $response->assertSuccessful();
        $response->assertViewIs('auth.passwords.reset');
        $response->assertViewHas('token', $reset_token);
    }

    public function test_cant_reset_password_with_invalid_token()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->from(route('password.reset', 'invalid_token'))->post(route('password.update'), [
            'token' => 'invalid_token',
            'email' => $user->email,
            'password' => 'new-awesome-password',
            'password_confirmation' => 'new-awesome-password',
        ]);

        $response->assertRedirect(route('password.reset', 'invalid_token'));
        $this->assertEquals($user->email, $user->fresh()->email);
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
        $this->assertGuest();
    }

    public function test_cant_reset_password_with_invalid_email()
    {
        $user = factory(User::class)->create([
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
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertEquals($user->email, $user->fresh()->email);
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
        $this->assertGuest();
    }

    public function test_cant_reset_password_with_invalid_password()
    {
        $user = factory(User::class)->create([
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
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertEquals($user->email, $user->fresh()->email);
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
        $this->assertGuest();
    }

    public function test_can_reset_password_with_valid_token()
    {
        Event::fake();
        $user = factory(User::class)->create();

        $response = $this->post(route('password.update'), [
            'token' => $this->getResetToken($user),
            'email' => $user->email,
            'password' => 'new-awesome-password',
            'password_confirmation' => 'new-awesome-password',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertEquals($user->email, $user->fresh()->email);
        $this->assertTrue(Hash::check('new-awesome-password', $user->fresh()->password));
        $this->assertAuthenticatedAs($user);
        Event::assertDispatched(PasswordReset::class, function ($e) use ($user) {
            return $e->user->id === $user->id;
        });
    }
}
