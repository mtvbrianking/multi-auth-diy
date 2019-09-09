<?php

namespace Tests\Feature\Auth;

use App\User;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @see \App\Http\Controllers\Auth\ForgotPasswordController
 */
class ForgotPasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_cant_visit_forgot_password_when_authenticated()
    {
        $user = factory(User::class)->make();

        $response = $this->actingAs($user)->get(route('password.request'));

        $response->assertRedirect(route('home'));
    }

    public function test_can_visit_forgot_password_when_not_authenticated()
    {
        $response = $this->get(route('password.request'));

        $response->assertSuccessful();
        $response->assertViewIs('auth.passwords.email');
    }

    public function test_cant_send_password_reset_email_to_non_registered_users()
    {
        Notification::fake();

        $response = $this->from(route('password.email'))->post(route('password.email'), [
            'email' => 'nobody@example.com',
        ]);

        $response->assertRedirect(route('password.email'));
        $response->assertSessionHasErrors('email');
        Notification::assertNotSentTo(factory(User::class)->make(['email' => 'nobody@example.com']), ResetPassword::class);
    }

    public function test_cant_send_password_reset_email_with_invalid_email_provided()
    {
        $response = $this->from(route('password.email'))->post(route('password.email'), [
            'email' => 'invalid-email',
        ]);

        $response->assertRedirect(route('password.email'));
        $response->assertSessionHasErrors('email');
    }

    public function test_can_send_email_with_password_reset_link_to_registered_users()
    {
        Notification::fake();
        $user = factory(User::class)->create([
            'email' => 'jdoe@example.com',
        ]);

        $this->post(route('password.email'), [
            'email' => 'jdoe@example.com',
        ]);

        $this->assertNotNull($token = DB::table('password_resets')->first());
        Notification::assertSentTo($user, ResetPassword::class, function ($notification, $channels) use ($token) {
            return Hash::check($notification->token, $token->token) === true;
        });
    }
}
