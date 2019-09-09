<?php

namespace Tests\Feature\Admin\Auth;

use App\Admin;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @see \App\Http\Controllers\Admin\Auth\RegisterController
 */
class RegisterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_cant_visit_register_when_authenticated()
    {
        $admin = factory(Admin::class)->make();

        $response = $this->actingAs($admin, 'admin')->get(route('admin.register'));

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_can_visit_register_when_not_authenticated()
    {
        $response = $this->get(route('admin.register'));

        $response->assertSuccessful();
        $response->assertViewIs('admin.auth.register');
    }

    public function test_cant_register_with_invalid_name()
    {
        $response = $this->from(route('admin.register'))->post(route('admin.register'), [
            'name' => '',
            'email' => 'jdoe@example.com',
            'password' => 'gJrFhC2B-!Y!4CTk',
            'password_confirmation' => 'gJrFhC2B-!Y!4CTk',
        ]);

        $admins = Admin::all();

        $this->assertCount(0, $admins);
        $response->assertRedirect(route('admin.register'));
        $response->assertSessionHasErrors('name');
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertGuest('admin');
    }

    public function test_cant_register_with_invalid_email()
    {
        $response = $this->from(route('admin.register'))->post(route('admin.register'), [
            'name' => 'John Doe',
            'email' => 'wrong.email-format',
            'password' => 'gJrFhC2B-!Y!4CTk',
            'password_confirmation' => 'gJrFhC2B-!Y!4CTk',
        ]);

        $admins = Admin::all();

        $this->assertCount(0, $admins);
        $response->assertRedirect(route('admin.register'));
        $response->assertSessionHasErrors('email');
        $this->assertTrue(session()->hasOldInput('name'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertGuest('admin');
    }

    public function test_cant_register_with_invalid_password()
    {
        $response = $this->from(route('admin.register'))->post(route('admin.register'), [
            'name' => 'John Doe',
            'email' => 'jdoe@example.com',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $admins = Admin::all();

        $this->assertCount(0, $admins);
        $response->assertRedirect(route('admin.register'));
        $response->assertSessionHasErrors('password');
        $this->assertTrue(session()->hasOldInput('name'));
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertGuest('admin');
    }

    public function test_cant_register_with_non_matched_passwords()
    {
        $response = $this->from(route('admin.register'))->post(route('admin.register'), [
            'name' => 'John Doe',
            'email' => 'jdoe@example.com',
            'password' => 'gJrFhC2B-!Y!4CTk',
            'password_confirmation' => '123456',
        ]);

        $admins = Admin::all();

        $this->assertCount(0, $admins);
        $response->assertRedirect(route('admin.register'));
        $response->assertSessionHasErrors('password');
        $this->assertTrue(session()->hasOldInput('name'));
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertGuest('admin');
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

        $response = $this->post(route('admin.register'), [
            'name' => 'John Doe',
            'email' => 'jdoe@example.com',
            'password' => 'gJrFhC2B-!Y!4CTk',
            'password_confirmation' => 'gJrFhC2B-!Y!4CTk',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertCount(1, $admins = Admin::all());
        $admin = $admins->first();
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertEquals('John Doe', $admin->name);
        $this->assertEquals('jdoe@example.com', $admin->email);
        $this->assertTrue(Hash::check('gJrFhC2B-!Y!4CTk', $admin->password));
        Event::assertDispatched(Registered::class, function ($e) use ($admin) {
            return $e->user->id === $admin->id;
        });
    }
}
