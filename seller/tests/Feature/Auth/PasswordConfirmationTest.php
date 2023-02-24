<?php

use Seller\Models\Seller;

test('confirm password screen can be rendered', function () {
    $seller = Seller::factory()->create();

    $response = $this->actingAs($seller, 'seller')->get('/seller/confirm-password');

    $response->assertStatus(200);
});

test('password can be confirmed', function () {
    $seller = Seller::factory()->create();

    $response = $this->actingAs($seller, 'seller')->post('/seller/confirm-password', [
        'password' => 'password',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
});

test('password is not confirmed with invalid password', function () {
    $seller = Seller::factory()->create();

    $response = $this->actingAs($seller, 'seller')->post('/seller/confirm-password', [
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors();
});
