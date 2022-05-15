<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Auth\RegisterController
 */
final class RegisterControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testCantVisitRegisterWhenAuthenticated()
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)->get(route('register'));

        $response->assertRedirect(route('home'));
    }

    public function testCanVisitRegisterWhenNotAuthenticated()
    {
        $response = $this->get(route('register'));

        $response->assertSuccessful();
        $response->assertViewIs('auth.register');
    }

    public function testCantRegisterWithInvalidName()
    {
        $response = $this->from(route('register'))->post(route('register'), [
            'name' => '',
            'email' => 'jdoe@example.com',
            'password' => 'gJrFhC2B-!Y!4CTk',
            'password_confirmation' => 'gJrFhC2B-!Y!4CTk',
        ]);

        $users = User::all();

        static::assertCount(0, $users);
        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('name');
        static::assertTrue(session()->hasOldInput('email'));
        static::assertFalse(session()->hasOldInput('password'));
        $this->assertGuest();
    }

    public function testCantRegisterWithInvalidEmail()
    {
        $response = $this->from(route('register'))->post(route('register'), [
            'name' => 'John Doe',
            'email' => 'wrong.email-format',
            'password' => 'gJrFhC2B-!Y!4CTk',
            'password_confirmation' => 'gJrFhC2B-!Y!4CTk',
        ]);

        $users = User::all();

        static::assertCount(0, $users);
        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('email');
        static::assertTrue(session()->hasOldInput('name'));
        static::assertFalse(session()->hasOldInput('password'));
        $this->assertGuest();
    }

    public function testCantRegisterWithInvalidPassword()
    {
        $response = $this->from(route('register'))->post(route('register'), [
            'name' => 'John Doe',
            'email' => 'jdoe@example.com',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $users = User::all();

        static::assertCount(0, $users);
        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('password');
        static::assertTrue(session()->hasOldInput('name'));
        static::assertTrue(session()->hasOldInput('email'));
        static::assertFalse(session()->hasOldInput('password'));
        $this->assertGuest();
    }

    public function testCantRegisterWithNonMatchedPasswords()
    {
        $response = $this->from(route('register'))->post(route('register'), [
            'name' => 'John Doe',
            'email' => 'jdoe@example.com',
            'password' => 'gJrFhC2B-!Y!4CTk',
            'password_confirmation' => '123456',
        ]);

        $users = User::all();

        static::assertCount(0, $users);
        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('password');
        static::assertTrue(session()->hasOldInput('name'));
        static::assertTrue(session()->hasOldInput('email'));
        static::assertFalse(session()->hasOldInput('password'));
        $this->assertGuest();
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

        $response = $this->post(route('register'), [
            'name' => 'John Doe',
            'email' => 'jdoe@example.com',
            'password' => 'gJrFhC2B-!Y!4CTk',
            'password_confirmation' => 'gJrFhC2B-!Y!4CTk',
        ]);

        $response->assertRedirect(route('home'));
        static::assertCount(1, $users = User::all());
        $this->assertAuthenticatedAs($user = $users->first());
        static::assertSame('John Doe', $user->name);
        static::assertSame('jdoe@example.com', $user->email);
        static::assertTrue(Hash::check('gJrFhC2B-!Y!4CTk', $user->password));
        Event::assertDispatched(Registered::class, function ($e) use ($user) {
            return $e->user->id === $user->id;
        });
    }
}
