<?php

namespace Tests\Feature\Admin\Auth;

use App\Models\Admin;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\Auth\ResetPasswordController
 */
class ResetPasswordControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function getResetToken($admin)
    {
        return Password::broker('admins')->createToken($admin);
    }

    public function test_cant_visit_reset_password_when_authenticated()
    {
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')->get(route('admin.password.reset', $this->getResetToken($admin)));

        $response->assertRedirect(route('admin.home'));
    }

    public function test_can_visit_reset_password_when_unauthenticated()
    {
        $admin = Admin::factory()->create();

        $reset_token = $this->getResetToken($admin);

        $response = $this->get(route('admin.password.reset', $reset_token));

        $response->assertSuccessful();
        $response->assertViewIs('admin.auth.passwords.reset');
        $response->assertViewHas('token', $reset_token);
    }

    public function test_cant_reset_password_with_invalid_token()
    {
        $admin = Admin::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->from(route('admin.password.reset', 'invalid_token'))->post(route('admin.password.update'), [
            'token' => 'invalid_token',
            'email' => $admin->email,
            'password' => 'new-awesome-password',
            'password_confirmation' => 'new-awesome-password',
        ]);

        $response->assertRedirect(route('admin.password.reset', 'invalid_token'));
        $this->assertEquals($admin->email, $admin->fresh()->email);
        $this->assertTrue(Hash::check('old-password', $admin->fresh()->password));
        $this->assertGuest('admin');
    }

    public function test_cant_reset_password_with_invalid_email()
    {
        $admin = Admin::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $reset_token = $this->getResetToken($admin);

        $response = $this->from(route('admin.password.reset', $reset_token))->post(route('admin.password.update'), [
            'token' => $reset_token,
            'email' => '',
            'password' => 'new-awesome-password',
            'password_confirmation' => 'new-awesome-password',
        ]);

        $response->assertRedirect(route('admin.password.reset', $reset_token));
        $response->assertSessionHasErrors('email');
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertEquals($admin->email, $admin->fresh()->email);
        $this->assertTrue(Hash::check('old-password', $admin->fresh()->password));
        $this->assertGuest('admin');
    }

    public function test_cant_reset_password_with_invalid_password()
    {
        $admin = Admin::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $reset_token = $this->getResetToken($admin);

        $response = $this->from(route('admin.password.reset', $reset_token))->post(route('admin.password.update'), [
            'token' => $reset_token,
            'email' => $admin->email,
            'password' => '87654321',
            'password_confirmation' => '12345678',
        ]);

        $response->assertRedirect(route('admin.password.reset', $reset_token));
        $response->assertSessionHasErrors('password');
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertEquals($admin->email, $admin->fresh()->email);
        $this->assertTrue(Hash::check('old-password', $admin->fresh()->password));
        $this->assertGuest('admin');
    }

    public function test_can_reset_password_with_valid_token()
    {
        Event::fake();

        $admin = Admin::factory()->create();

        $reset_token = $this->getResetToken($admin);

        $response = $this->post(route('admin.password.update'), [
            'token' => $reset_token,
            'email' => $admin->email,
            'password' => 'new-awesome-password',
            'password_confirmation' => 'new-awesome-password',
        ]);

        $response->assertRedirect(route('admin.home'));
        $this->assertEquals($admin->email, $admin->fresh()->email);
        $this->assertTrue(Hash::check('new-awesome-password', $admin->fresh()->password));
        $this->assertAuthenticatedAs($admin, 'admin');
        Event::assertDispatched(PasswordReset::class, function ($e) use ($admin) {
            return $e->user->id === $admin->id;
        });
    }
}
