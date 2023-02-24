<?php

use Illuminate\Support\Str;
use Seller\Models\Seller;

test('login screen can be rendered', function () {
    $response = $this->get('/seller/login');

    $response->assertStatus(200);

    // ...

    $seller = Seller::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($seller, 'seller')->get(route('seller.login'));

    $response->assertRedirect('/seller');
});

test('users can authenticate using the login screen', function () {
    $seller = Seller::factory()->create();

    $response = $this->post('/seller/login', [
        'email' => $seller->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($seller, 'seller');
    $response->assertRedirect('/seller');
});

test('users can not authenticate with invalid password', function () {
    $seller = Seller::factory()->create();

    $this->post('/seller/login', [
        'email' => $seller->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest('seller');
});

test('can logout if authenticated', function () {
    $seller = Seller::factory()->create();

    $this->be($seller, 'seller');

    $response = $this->post(route('seller.logout'));

    $response->assertRedirect(route('seller.dashboard'));
    $this->assertGuest('seller');
});

test('can not make more than five failed login attempts a minute', function () {
    $seller = Seller::factory()->create();

    foreach (range(0, 5) as $_) {
        $response = $this->from(route('seller.login'))->post(route('seller.login'), [
            'email' => $seller->email,
            'password' => Str::random(10),
        ]);
    }

    $response->assertRedirect(route('seller.login'));
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
    $this->assertGuest('seller');
});
