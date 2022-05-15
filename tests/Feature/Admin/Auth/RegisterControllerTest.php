<?php

namespace Tests\Feature\Admin\Auth;

use App\Models\Admin;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\Auth\RegisterController
 */
final class RegisterControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testCantVisitRegisterWhenAuthenticated()
    {
        $admin = Admin::factory()->make();

        $response = $this->actingAs($admin, 'admin')->get(route('admin.register'));

        $response->assertRedirect(route('admin.home'));
    }

    public function testCanVisitRegisterWhenNotAuthenticated()
    {
        $response = $this->get(route('admin.register'));

        $response->assertSuccessful();
        $response->assertViewIs('admin.auth.register');
    }

    public function testCantRegisterWithInvalidName()
    {
        $response = $this->from(route('admin.register'))->post(route('admin.register'), [
            'name' => '',
            'email' => 'jdoe@example.com',
            'password' => 'gJrFhC2B-!Y!4CTk',
            'password_confirmation' => 'gJrFhC2B-!Y!4CTk',
        ]);

        $admins = Admin::all();

        static::assertCount(0, $admins);
        $response->assertRedirect(route('admin.register'));
        $response->assertSessionHasErrors('name');
        static::assertTrue(session()->hasOldInput('email'));
        static::assertFalse(session()->hasOldInput('password'));
        $this->assertGuest('admin');
    }

    public function testCantRegisterWithInvalidEmail()
    {
        $response = $this->from(route('admin.register'))->post(route('admin.register'), [
            'name' => 'John Doe',
            'email' => 'wrong.email-format',
            'password' => 'gJrFhC2B-!Y!4CTk',
            'password_confirmation' => 'gJrFhC2B-!Y!4CTk',
        ]);

        $admins = Admin::all();

        static::assertCount(0, $admins);
        $response->assertRedirect(route('admin.register'));
        $response->assertSessionHasErrors('email');
        static::assertTrue(session()->hasOldInput('name'));
        static::assertFalse(session()->hasOldInput('password'));
        $this->assertGuest('admin');
    }

    public function testCantRegisterWithInvalidPassword()
    {
        $response = $this->from(route('admin.register'))->post(route('admin.register'), [
            'name' => 'John Doe',
            'email' => 'jdoe@example.com',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $admins = Admin::all();

        static::assertCount(0, $admins);
        $response->assertRedirect(route('admin.register'));
        $response->assertSessionHasErrors('password');
        static::assertTrue(session()->hasOldInput('name'));
        static::assertTrue(session()->hasOldInput('email'));
        static::assertFalse(session()->hasOldInput('password'));
        $this->assertGuest('admin');
    }

    public function testCantRegisterWithNonMatchedPasswords()
    {
        $response = $this->from(route('admin.register'))->post(route('admin.register'), [
            'name' => 'John Doe',
            'email' => 'jdoe@example.com',
            'password' => 'gJrFhC2B-!Y!4CTk',
            'password_confirmation' => '123456',
        ]);

        $admins = Admin::all();

        static::assertCount(0, $admins);
        $response->assertRedirect(route('admin.register'));
        $response->assertSessionHasErrors('password');
        static::assertTrue(session()->hasOldInput('name'));
        static::assertTrue(session()->hasOldInput('email'));
        static::assertFalse(session()->hasOldInput('password'));
        $this->assertGuest('admin');
    }

    /**
     * @see https://github.com/laravel/framework/issues/18066#issuecomment-342630971 Issue 18066
     */
    public function testCanRegisterWithValidInfo()
    {
        $this->withoutExceptionHandling();

        // Illuminate\Tests\Integration\Events\EventFakeTest
        $initialDispatcher = Event::getFacadeRoot();
        Event::fake();
        Model::setEventDispatcher($initialDispatcher);

        $response = $this->post(route('admin.register'), [
            'name' => 'John Doe',
            'email' => 'jdoe@example.com',
            'password' => 'gJrFhC2B-!Y!4CTk',
            'password_confirmation' => 'gJrFhC2B-!Y!4CTk',
        ]);

        $response->assertRedirect(route('admin.home'));
        static::assertCount(1, $admins = Admin::all());
        $admin = $admins->first();
        $this->assertAuthenticatedAs($admin, 'admin');
        static::assertSame('John Doe', $admin->name);
        static::assertSame('jdoe@example.com', $admin->email);
        static::assertTrue(Hash::check('gJrFhC2B-!Y!4CTk', $admin->password));
        Event::assertDispatched(Registered::class, function ($e) use ($admin) {
            return $e->user->id === $admin->id;
        });
    }
}
