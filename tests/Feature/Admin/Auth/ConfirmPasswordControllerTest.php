<?php

namespace Tests\Feature\Admin\Auth;

use App\Models\Admin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\Auth\ConfirmPasswordController
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

        Route::name('admin.password.protected')
            ->middleware([
                'web',
                'admin.auth:admin',
                'admin.password.confirm',
            ])
            ->get('admin/protected', function (Request $request) {
                return response('Admins must confirm their password before reading this.');
            })
        ;
    }

    public function testCantVisitConfirmPasswordWhenNotAuthenticated()
    {
        $response = $this->get(route('admin.password.protected'));

        $response->assertRedirect(route('admin.login'));
    }

    public function testCanVisitConfirmPasswordWhenAuthenticated()
    {
        $admin = Admin::factory()->make();

        $response = $this->actingAs($admin, 'admin')->get(route('admin.password.protected'));

        $response->assertRedirect(route('admin.password.confirm'));
    }

    public function testCanVisitConfirmPasswordWhenAuthenticatedJson()
    {
        $admin = Admin::factory()->make();

        $response = $this->actingAs($admin, 'admin')
            ->withHeader('Accept', 'application/json')
            ->get(route('admin.password.protected'))
        ;

        $response->assertStatus(423);

        $response->assertJson([
            'message' => 'Password confirmation required.',
        ]);
    }

    public function testShowsAdminConfirmPasswordPage()
    {
        $admin = Admin::factory()->make();

        $response = $this->actingAs($admin, 'admin')->get(route('admin.password.confirm'));

        $response->assertViewIs('admin.auth.passwords.confirm');
    }

    public function testCantConfirmWithInvalidPassword()
    {
        $admin = Admin::factory()->create([
            'password' => Hash::make('gJrFhC2B-!Y!4CTk'),
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.password.confirm'))
            ->post(route('admin.password.confirm'), [
                'password' => 'invalid-password',
            ])
        ;

        $response->assertRedirect(route('admin.password.confirm'));
        static::assertFalse(session()->hasOldInput('password'));
    }

    public function testCanConfirmWithValidPassword()
    {
        $admin = Admin::factory()->create([
            'password' => Hash::make('gJrFhC2B-!Y!4CTk'),
        ]);

        $response = $this->actingAs($admin, 'admin')
            // ->from(route('admin.password.protected'))
            ->from(route('admin.password.confirm'))
            ->post(route('admin.password.confirm'), [
                'password' => 'gJrFhC2B-!Y!4CTk',
            ])
        ;

        $response->assertRedirect(route('admin.home'));
    }

    public function testShouldNotReconfirmPasswordNotIfTimedOut()
    {
        $this->app['session']->put('admin.auth.password_confirmed_at', time());

        $this->app['config']->set('auth.password_timeout', 100);

        $admin = Admin::factory()->create([
            'password' => Hash::make('gJrFhC2B-!Y!4CTk'),
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.password.protected'));

        $response->assertStatus(200);
    }

    public function testShouldReconfirmPasswordIfTimedOut()
    {
        $passwordConfirmedAt = time() - 100;

        $this->app['session']->put('admin.auth.password_confirmed_at', $passwordConfirmedAt);

        $this->app['config']->set('auth.password_timeout', 10);

        $admin = Admin::factory()->create([
            'password' => Hash::make('gJrFhC2B-!Y!4CTk'),
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.password.protected'));

        $response->assertRedirect(route('admin.password.confirm'));
    }
}
