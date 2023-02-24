<?php

use App\Models\Admin;
use Illuminate\Support\Str;

test('login screen can be rendered', function () {
    $response = $this->get('/admin/login');

    $response->assertStatus(200);

    // ...

    $admin = Admin::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($admin, 'admin')->get(route('admin.login'));

    $response->assertRedirect('/admin');
});

test('users can authenticate using the login screen', function () {
    $admin = Admin::factory()->create();

    $response = $this->post('/admin/login', [
        'email' => $admin->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($admin, 'admin');
    $response->assertRedirect('/admin');
});

test('users can not authenticate with invalid password', function () {
    $admin = Admin::factory()->create();

    $this->post('/admin/login', [
        'email' => $admin->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest('admin');
});

test('can logout if authenticated', function () {
    $admin = Admin::factory()->create();

    $this->be($admin, 'admin');

    $response = $this->post(route('admin.logout'));

    $response->assertRedirect(route('admin.dashboard'));
    $this->assertGuest('admin');
});

test('can not make more than five failed login attempts a minute', function () {
    $admin = Admin::factory()->create();

    foreach (range(0, 5) as $_) {
        $response = $this->from(route('admin.login'))->post(route('admin.login'), [
            'email' => $admin->email,
            'password' => Str::random(10),
        ]);
    }

    $response->assertRedirect(route('admin.login'));
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
    $this->assertGuest('admin');
});
