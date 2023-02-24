<?php

use Illuminate\Support\Facades\Notification;
use Seller\Models\Seller;
use Seller\Notifications\Auth\ResetPassword;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/seller/forgot-password');

    $response->assertStatus(200);
});

test('reset password link can be requested', function () {
    Notification::fake();

    $seller = Seller::factory()->create();

    $this->post('/seller/forgot-password', ['email' => $seller->email]);

    Notification::assertSentTo($seller, ResetPassword::class);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $seller = Seller::factory()->create();

    $this->post('/seller/forgot-password', ['email' => $seller->email]);

    Notification::assertSentTo($seller, ResetPassword::class, function ($notification) {
        $response = $this->get('/seller/reset-password/'.$notification->token);

        $response->assertStatus(200);

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $seller = Seller::factory()->create();

    $this->post('/seller/forgot-password', ['email' => $seller->email]);

    Notification::assertSentTo($seller, ResetPassword::class, function ($notification) use ($seller) {
        $response = $this->post('/seller/reset-password', [
            'token' => $notification->token,
            'email' => $seller->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors();

        return true;
    });
});
