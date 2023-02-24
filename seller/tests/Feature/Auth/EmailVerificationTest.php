<?php

use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Seller\Models\Seller;
use Seller\Notifications\Auth\VerifyEmail;

test('email verification screen can be rendered', function () {
    $verifiedSeller = Seller::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($verifiedSeller, 'seller')->get('/seller/verify-email');

    $response->assertRedirect('/seller');

    // ...

    $nonVerifiedSeller = Seller::factory()->create(['email_verified_at' => null]);

    $response = $this->actingAs($nonVerifiedSeller, 'seller')->get('/seller/verify-email');

    $response->assertStatus(200);
});

test('email can be verified', function () {
    $nonVerifiedSeller = Seller::factory()->create(['email_verified_at' => null]);

    $verifiedSeller = Seller::factory()->create(['email_verified_at' => now()]);

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute('seller.verification.verify', now()->addMinutes(60), [
        'id' => $verifiedSeller->id,
        'hash' => sha1($verifiedSeller->email),
    ]);

    $response = $this->actingAs($verifiedSeller, 'seller')->get($verificationUrl);

    Event::assertNotDispatched(Verified::class);

    $response->assertRedirect('/seller'.'?verified=1');

    // ...

    $verificationUrl = URL::temporarySignedRoute('seller.verification.verify', now()->addMinutes(60), [
        'id' => $nonVerifiedSeller->id,
        'hash' => sha1($nonVerifiedSeller->email),
    ]);

    $response = $this->actingAs($nonVerifiedSeller, 'seller')->get($verificationUrl);

    Event::assertDispatched(Verified::class);
    expect($nonVerifiedSeller->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect('/seller'.'?verified=1');
});

test('email is not verified with invalid hash', function () {
    $seller = Seller::factory()->create(['email_verified_at' => null]);

    $verificationUrl = URL::temporarySignedRoute('seller.verification.verify', now()->addMinutes(60), [
        'id' => $seller->id,
        'hash' => sha1('wrong-email'),
    ]);

    $this->actingAs($seller, 'seller')->get($verificationUrl);

    expect($seller->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('resends email verification link', function () {
    $verifiedSeller = Seller::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($verifiedSeller, 'seller')->post(route('seller.verification.send'));

    $response->assertRedirect('/seller');

    // ...

    Notification::fake();

    $nonVerifiedSeller = Seller::factory()->create(['email_verified_at' => null]);

    $response = $this->actingAs($nonVerifiedSeller, 'seller')
        ->from(route('seller.verification.notice'))
        ->post(route('seller.verification.send'));

    Notification::assertSentTo($nonVerifiedSeller, VerifyEmail::class);

    $response->assertRedirect(route('seller.verification.notice'));
});
