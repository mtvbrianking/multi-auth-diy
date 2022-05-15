<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Auth\ConfirmPasswordController
 *
 * @internal
 * @coversNothing
 */
final class ConfirmPasswordControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Route::name('password.protected')
            ->middleware([
                'web',
                'auth',
                'password.confirm',
            ])
            ->get('protected', function (Request $request) {
                return response('Users must confirm their password before reading this.');
            })
        ;
    }

    public function testCantVisitConfirmPasswordWhenNotAuthenticated()
    {
        $response = $this->get(route('password.protected'));

        $response->assertRedirect(route('login'));
    }

    public function testCanVisitConfirmPasswordWhenAuthenticated()
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)->get(route('password.protected'));

        $response->assertRedirect(route('password.confirm'));
    }

    public function testCantConfirmWithInvalidPassword()
    {
        $user = User::factory()->create([
            'password' => Hash::make('gJrFhC2B-!Y!4CTk'),
        ]);

        $response = $this->actingAs($user)
            ->from(route('password.confirm'))
            ->post(route('password.confirm'), [
                'password' => 'invalid-password',
            ])
        ;

        $response->assertRedirect(route('password.confirm'));
        static::assertFalse(session()->hasOldInput('password'));
    }

    public function testCanConfirmWithValidPassword()
    {
        $user = User::factory()->create([
            'password' => Hash::make('gJrFhC2B-!Y!4CTk'),
        ]);

        $response = $this->actingAs($user)
            // ->from(route('password.protected'))
            ->from(route('password.confirm'))
            ->post(route('password.confirm'), [
                'password' => 'gJrFhC2B-!Y!4CTk',
            ])
        ;

        $response->assertRedirect(route('home'));
    }

    public function testShouldNotReconfirmPasswordNotIfTimedOut()
    {
        $this->app['session']->put('auth.password_confirmed_at', time());

        $this->app['config']->set('auth.password_timeout', 100);

        $user = User::factory()->create([
            'password' => Hash::make('gJrFhC2B-!Y!4CTk'),
        ]);

        $response = $this->actingAs($user)->get(route('password.protected'));

        $response->assertStatus(200);
    }

    public function testShouldReconfirmPasswordIfTimedOut()
    {
        $passwordConfirmedAt = time() - 100;

        $this->app['session']->put('auth.password_confirmed_at', $passwordConfirmedAt);

        $this->app['config']->set('auth.password_timeout', 10);

        $user = User::factory()->create([
            'password' => Hash::make('gJrFhC2B-!Y!4CTk'),
        ]);

        $response = $this->actingAs($user)->get(route('password.protected'));

        $response->assertRedirect(route('password.confirm'));
    }
}
