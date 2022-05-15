<?php

namespace Tests\Feature\Admin\Auth;

use App\Models\Admin;
use App\Notifications\Admin\Auth\ResetPassword;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\Auth\ForgotPasswordController
 */
final class ForgotPasswordControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testCantVisitForgotPasswordWhenAuthenticated()
    {
        $admin = Admin::factory()->make();

        $response = $this->actingAs($admin, 'admin')->get(route('admin.password.request'));

        $response->assertRedirect(route('admin.home'));
    }

    public function testCanVisitForgotPasswordWhenNotAuthenticated()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(route('admin.password.request'));

        $response->assertSuccessful();
        $response->assertViewIs('admin.auth.passwords.email');
    }

    public function testCantSendPasswordResetEmailToNonRegisteredUsers()
    {
        Notification::fake();

        $response = $this->from(route('admin.password.email'))->post(route('admin.password.email'), [
            'email' => 'nobody@example.com',
        ]);

        $response->assertRedirect(route('admin.password.email'));
        $response->assertSessionHasErrors('email');
        Notification::assertNotSentTo(Admin::factory()->make(['email' => 'nobody@example.com']), ResetPassword::class);
    }

    public function testCantSendPasswordResetEmailWithInvalidEmailProvided()
    {
        $response = $this->from(route('admin.password.email'))->post(route('admin.password.email'), [
            'email' => 'invalid-email',
        ]);

        $response->assertRedirect(route('admin.password.email'));
        $response->assertSessionHasErrors('email');
    }

    public function testCanSendEmailWithPasswordResetLinkToRegisteredUsers()
    {
        Notification::fake();

        $admin = Admin::factory()->create([
            'email' => 'jdoe@example.com',
        ]);

        $this->post(route('admin.password.email'), [
            'email' => 'jdoe@example.com',
        ]);

        static::assertNotNull($token = DB::table('admin_password_resets')->first());
        Notification::assertSentTo($admin, ResetPassword::class, function ($notification, $channels) use ($token) {
            return true === Hash::check($notification->token, $token->token);
        });
    }
}
