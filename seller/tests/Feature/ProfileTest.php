<?php

use Seller\Models\Seller;

test('profile page is displayed', function () {
    $seller = Seller::factory()->create();

    $response = $this
        ->actingAs($seller, 'seller')
        ->get('/seller/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $seller = Seller::factory()->create();

    $response = $this
        ->actingAs($seller, 'seller')
        ->patch('/seller/profile', [
            'name' => 'Test Seller',
            'email' => 'seller@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/seller/profile');

    $seller->refresh();

    $this->assertSame('Test Seller', $seller->name);
    $this->assertSame('seller@example.com', $seller->email);
    $this->assertNull($seller->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $seller = Seller::factory()->create();

    $response = $this
        ->actingAs($seller, 'seller')
        ->patch('/seller/profile', [
            'name' => 'Test Seller',
            'email' => $seller->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/seller/profile');

    $this->assertNotNull($seller->refresh()->email_verified_at);
});

test('user can delete their account', function () {
    $seller = Seller::factory()->create();

    $response = $this
        ->actingAs($seller, 'seller')
        ->delete('/seller/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/seller');

    $this->assertGuest();
    $this->assertNull($seller->fresh());
});

test('correct password must be provided to delete account', function () {
    $seller = Seller::factory()->create();

    $response = $this
        ->actingAs($seller, 'seller')
        ->from('/seller/profile')
        ->delete('/seller/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/seller/profile');

    $this->assertNotNull($seller->fresh());
});
