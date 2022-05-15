<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Auth\ForgotPasswordController
 *
 * @internal
 * @coversNothing
 */
final class ForgotPasswordControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testCantVisitForgotPasswordWhenAuthenticated()
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)->get(route('password.request'));

        $response->assertRedirect(route('home'));
    }

    public function testCanVisitForgotPasswordWhenNotAuthenticated()
    {
        $response = $this->get(route('password.request'));

        $response->assertSuccessful();
        $response->assertViewIs('auth.passwords.email');
    }

    public function testCantSendPasswordResetEmailToNonRegisteredUsers()
    {
        Notification::fake();

        $response = $this->from(route('password.email'))->post(route('password.email'), [
            'email' => 'nobody@example.com',
        ]);

        $response->assertRedirect(route('password.email'));
        $response->assertSessionHasErrors('email');
        Notification::assertNotSentTo(User::factory()->make(['email' => 'nobody@example.com']), ResetPassword::class);
    }

    public function testCantSendPasswordResetEmailWithInvalidEmailProvided()
    {
        $response = $this->from(route('password.email'))->post(route('password.email'), [
            'email' => 'invalid-email',
        ]);

        $response->assertRedirect(route('password.email'));
        $response->assertSessionHasErrors('email');
    }

    public function testCanSendEmailWithPasswordResetLinkToRegisteredUsers()
    {
        Notification::fake();
        $user = User::factory()->create([
            'email' => 'jdoe@example.com',
        ]);

        $this->post(route('password.email'), [
            'email' => 'jdoe@example.com',
        ]);

        static::assertNotNull($token = DB::table('password_resets')->first());
        Notification::assertSentTo($user, ResetPassword::class, function ($notification, $channels) use ($token) {
            return true === Hash::check($notification->token, $token->token);
        });
    }
}
