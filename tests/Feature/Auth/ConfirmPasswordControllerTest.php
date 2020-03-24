<?php

namespace Tests\Feature\Auth;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Auth\ConfirmPasswordController
 */
class ConfirmPasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_cant_visit_confirm_password_when_not_authenticated()
    {
        $response = $this->get(route('password.protected'));

        $response->assertRedirect(route('login'));
    }

    public function test_can_visit_confirm_password_when_authenticated()
    {
        $user = factory(User::class)->make();

        $response = $this->actingAs($user)->get(route('password.protected'));

        $response->assertRedirect(route('password.confirm'));
    }

    public function test_cant_confirm_with_invalid_password()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('gJrFhC2B-!Y!4CTk'),
        ]);

        $response = $this->actingAs($user)
            ->from(route('password.confirm'))
            ->post(route('password.confirm'), [
                'password' => 'invalid-password',
            ]);

        $response->assertRedirect(route('password.confirm'));
        $this->assertFalse(session()->hasOldInput('password'));
    }

    public function test_can_confirm_with_valid_password()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('gJrFhC2B-!Y!4CTk'),
        ]);

        $response = $this->actingAs($user)
            // ->from(route('password.protected'))
            ->from(route('password.confirm'))
            ->post(route('password.confirm'), [
                'password' => 'gJrFhC2B-!Y!4CTk',
            ]);

        $response->assertRedirect(route('home'));
    }

    public function test_should_not_reconfirm_password_not_if_timed_out()
    {
        $this->app['session']->put('auth.password_confirmed_at', time());

        $this->app['config']->set('auth.password_timeout', 100);

        $user = factory(User::class)->create([
            'password' => Hash::make('gJrFhC2B-!Y!4CTk'),
        ]);

        $response = $this->actingAs($user)->get(route('password.protected'));

        $response->assertStatus(200);
    }

    public function test_should_reconfirm_password_if_timed_out()
    {
        $passwordConfirmedAt = time() - 100;

        $this->app['session']->put('auth.password_confirmed_at', $passwordConfirmedAt);

        $this->app['config']->set('auth.password_timeout', 10);

        $user = factory(User::class)->create([
            'password' => Hash::make('gJrFhC2B-!Y!4CTk'),
        ]);

        $response = $this->actingAs($user)->get(route('password.protected'));

        $response->assertRedirect(route('password.confirm'));
    }
}
