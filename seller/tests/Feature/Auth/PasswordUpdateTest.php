<?php

use Illuminate\Support\Facades\Hash;
use Seller\Models\Seller;

test('password can be updated', function () {
    $seller = Seller::factory()->create();

    $response = $this
        ->actingAs($seller, 'seller')
        ->from('/seller/profile')
        ->put('/seller/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/seller/profile');

    $this->assertTrue(Hash::check('new-password', $seller->refresh()->password));
});

test('correct password must be provided to update password', function () {
    $seller = Seller::factory()->create();

    $response = $this
        ->actingAs($seller, 'seller')
        ->from('/seller/profile')
        ->put('/seller/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('updatePassword', 'current_password')
        ->assertRedirect('/seller/profile');
});
