<?php

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

test('email verification screen can be rendered', function () {
    $verifiedUser = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($verifiedUser)->get('/verify-email');

    $response->assertRedirect(RouteServiceProvider::HOME);

    // ...

    $nonVerifiedUser = User::factory()->create(['email_verified_at' => null]);

    $response = $this->actingAs($nonVerifiedUser)->get('/verify-email');

    $response->assertStatus(200);
});

test('email can be verified', function () {
    $nonVerifiedUser = User::factory()->create(['email_verified_at' => null]);

    $verifiedUser = User::factory()->create(['email_verified_at' => now()]);

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $verifiedUser->id,
        'hash' => sha1($verifiedUser->email),
    ]);

    $response = $this->actingAs($verifiedUser)->get($verificationUrl);

    Event::assertNotDispatched(Verified::class);

    $response->assertRedirect(RouteServiceProvider::HOME.'?verified=1');

    // ...

    $verificationUrl = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $nonVerifiedUser->id,
        'hash' => sha1($nonVerifiedUser->email),
    ]);

    $response = $this->actingAs($nonVerifiedUser)->get($verificationUrl);

    Event::assertDispatched(Verified::class);
    expect($nonVerifiedUser->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(RouteServiceProvider::HOME.'?verified=1');
});

test('email is not verified with invalid hash', function () {
    $user = User::factory()->create(['email_verified_at' => null]);

    $verificationUrl = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->id,
        'hash' => sha1('wrong-email'),
    ]);

    $this->actingAs($user)->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('resends email verification link', function () {
    $verifiedUser = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($verifiedUser)->post(route('verification.send'));

    $response->assertRedirect(RouteServiceProvider::HOME);

    // ...

    Notification::fake();

    $nonVerifiedUser = User::factory()->create(['email_verified_at' => null]);

    $response = $this->actingAs($nonVerifiedUser)
        ->from(route('verification.notice'))
        ->post(route('verification.send'));

    Notification::assertSentTo($nonVerifiedUser, VerifyEmail::class);

    $response->assertRedirect(route('verification.notice'));
});
