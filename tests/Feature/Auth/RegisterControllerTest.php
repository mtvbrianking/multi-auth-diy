<?php

namespace Tests\Feature\Auth;

use App\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @see \App\Http\Controllers\Auth\RegisterController
 */
class RegisterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_cant_visit_register_when_authenticated()
    {
        $user = factory(User::class)->make();

        $response = $this->actingAs($user)->get(route('register'));

        $response->assertRedirect(route('home'));
    }

    public function test_can_visit_register_when_not_authenticated()
    {
        $response = $this->get(route('register'));

        $response->assertSuccessful();
        $response->assertViewIs('auth.register');
    }

    public function test_cant_register_with_invalid_name()
    {
        $response = $this->from(route('register'))->post(route('register'), [
            'name' => '',
            'email' => 'jdoe@example.com',
            'password' => 'gJrFhC2B-!Y!4CTk',
            'password_confirmation' => 'gJrFhC2B-!Y!4CTk',
        ]);

        $users = User::all();

        $this->assertCount(0, $users);
        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('name');
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertGuest();
    }

    public function test_cant_register_with_invalid_email()
    {
        $response = $this->from(route('register'))->post(route('register'), [
            'name' => 'John Doe',
            'email' => 'wrong.email-format',
            'password' => 'gJrFhC2B-!Y!4CTk',
            'password_confirmation' => 'gJrFhC2B-!Y!4CTk',
        ]);

        $users = User::all();

        $this->assertCount(0, $users);
        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('email');
        $this->assertTrue(session()->hasOldInput('name'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertGuest();
    }

    public function test_cant_register_with_invalid_password()
    {
        $response = $this->from(route('register'))->post(route('register'), [
            'name' => 'John Doe',
            'email' => 'jdoe@example.com',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $users = User::all();

        $this->assertCount(0, $users);
        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('password');
        $this->assertTrue(session()->hasOldInput('name'));
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertGuest();
    }

    public function test_cant_register_with_non_matched_passwords()
    {
        $response = $this->from(route('register'))->post(route('register'), [
            'name' => 'John Doe',
            'email' => 'jdoe@example.com',
            'password' => 'gJrFhC2B-!Y!4CTk',
            'password_confirmation' => '123456',
        ]);

        $users = User::all();

        $this->assertCount(0, $users);
        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('password');
        $this->assertTrue(session()->hasOldInput('name'));
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertGuest();
    }

    /**
     * @see https://github.com/laravel/framework/issues/18066#issuecomment-342630971 Issue 18066
     */
    public function test_can_register_with_valid_info()
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
        $this->assertCount(1, $users = User::all());
        $this->assertAuthenticatedAs($user = $users->first());
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('jdoe@example.com', $user->email);
        $this->assertTrue(Hash::check('gJrFhC2B-!Y!4CTk', $user->password));
        Event::assertDispatched(Registered::class, function ($e) use ($user) {
            return $e->user->id === $user->id;
        });
    }
}
