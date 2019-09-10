<?php

namespace Tests\Feature\Admin\Auth;

use App\Admin;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Admin\Auth\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @see \App\Http\Controllers\Admin\Auth\ForgotPasswordController
 */
class ForgotPasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_cant_visit_forgot_password_when_authenticated()
    {
        $admin = factory(Admin::class)->make();

        $response = $this->actingAs($admin, 'admin')->get(route('admin.password.request'));

        $response->assertRedirect(route('admin.home'));
    }

    public function test_can_visit_forgot_password_when_not_authenticated()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(route('admin.password.request'));

        $response->assertSuccessful();
        $response->assertViewIs('admin.auth.passwords.email');
    }

    public function test_cant_send_password_reset_email_to_non_registered_users()
    {
        Notification::fake();

        $response = $this->from(route('admin.password.email'))->post(route('admin.password.email'), [
            'email' => 'nobody@example.com',
        ]);

        $response->assertRedirect(route('admin.password.email'));
        $response->assertSessionHasErrors('email');
        Notification::assertNotSentTo(factory(Admin::class)->make(['email' => 'nobody@example.com']), ResetPassword::class);
    }

    public function test_cant_send_password_reset_email_with_invalid_email_provided()
    {
        $response = $this->from(route('admin.password.email'))->post(route('admin.password.email'), [
            'email' => 'invalid-email',
        ]);

        $response->assertRedirect(route('admin.password.email'));
        $response->assertSessionHasErrors('email');
    }

    public function test_can_send_email_with_password_reset_link_to_registered_users()
    {
        Notification::fake();

        $admin = factory(Admin::class)->create([
            'email' => 'jdoe@example.com',
        ]);

        $this->post(route('admin.password.email'), [
            'email' => 'jdoe@example.com',
        ]);

        $this->assertNotNull($token = DB::table('admin_password_resets')->first());
        Notification::assertSentTo($admin, ResetPassword::class, function ($notification, $channels) use ($token) {
            return Hash::check($notification->token, $token->token) === true;
        });
    }
}
