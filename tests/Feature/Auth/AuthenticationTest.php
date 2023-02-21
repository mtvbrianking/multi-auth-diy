<?php

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Str;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);

    // ...

    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($user)->get(route('login'));

    $response->assertRedirect(RouteServiceProvider::HOME);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(RouteServiceProvider::HOME);
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('can logout if authenticated', function () {
    $this->be(User::factory()->create());

    $response = $this->post(route('logout'));

    $response->assertRedirect(url('/'));
    $this->assertGuest();
});

test('can not make more than five failed login attempts a minute', function () {
    $user = User::factory()->create();

    foreach (range(0, 5) as $_) {
        $response = $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => Str::random(10),
        ]);
    }

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('email');
    static::assertStringContainsString(
        'Too many login attempts.',
        collect(
            $response
                ->baseResponse
                ->getSession()
                ->get('errors')
                ->getBag('default')
                ->get('email')
        )->first()
    );
    static::assertTrue(session()->hasOldInput('email'));
    static::assertFalse(session()->hasOldInput('password'));
    $this->assertGuest();
});
