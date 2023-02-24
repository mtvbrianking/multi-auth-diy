<?php

use Seller\Models\Seller;

test('registration screen can be rendered', function () {
    $response = $this->get('/seller/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/seller/register', [
        'name' => 'Test Seller',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticatedAs(Seller::first(), 'seller');
    $response->assertRedirect('/seller');
});
