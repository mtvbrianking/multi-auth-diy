<?php

use App\Models\Admin;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Verified;
use App\Notifications\Admin\Auth\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

test('email verification screen can be rendered', function () {
    $verifiedAdmin = Admin::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($verifiedAdmin, 'admin')->get('/admin/verify-email');

    $response->assertRedirect(RouteServiceProvider::ADMIN_HOME);

    // ...

    $nonVerifiedAdmin = Admin::factory()->create(['email_verified_at' => null]);

    $response = $this->actingAs($nonVerifiedAdmin, 'admin')->get('/admin/verify-email');

    $response->assertStatus(200);
});

test('email can be verified', function () {
    $nonVerifiedAdmin = Admin::factory()->create(['email_verified_at' => null]);

    $verifiedAdmin = Admin::factory()->create(['email_verified_at' => now()]);

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute('admin.verification.verify', now()->addMinutes(60), [
        'id' => $verifiedAdmin->id,
        'hash' => sha1($verifiedAdmin->email),
    ]);

    $response = $this->actingAs($verifiedAdmin, 'admin')->get($verificationUrl);

    Event::assertNotDispatched(Verified::class);

    $response->assertRedirect(RouteServiceProvider::ADMIN_HOME.'?verified=1');

    // ...

    $verificationUrl = URL::temporarySignedRoute('admin.verification.verify', now()->addMinutes(60), [
        'id' => $nonVerifiedAdmin->id,
        'hash' => sha1($nonVerifiedAdmin->email),
    ]);

    $response = $this->actingAs($nonVerifiedAdmin, 'admin')->get($verificationUrl);

    Event::assertDispatched(Verified::class);
    expect($nonVerifiedAdmin->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(RouteServiceProvider::ADMIN_HOME.'?verified=1');
});

test('email is not verified with invalid hash', function () {
    $admin = Admin::factory()->create(['email_verified_at' => null]);

    $verificationUrl = URL::temporarySignedRoute('admin.verification.verify', now()->addMinutes(60), [
        'id' => $admin->id,
        'hash' => sha1('wrong-email'),
    ]);

    $this->actingAs($admin, 'admin')->get($verificationUrl);

    expect($admin->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('resends email verification link', function () {
    $verifiedAdmin = Admin::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($verifiedAdmin, 'admin')->post(route('admin.verification.send'));

    $response->assertRedirect(RouteServiceProvider::ADMIN_HOME);

    // ...

    Notification::fake();

    $nonVerifiedAdmin = Admin::factory()->create(['email_verified_at' => null]);

    $response = $this->actingAs($nonVerifiedAdmin, 'admin')
        ->from(route('admin.verification.notice'))
        ->post(route('admin.verification.send'));

    Notification::assertSentTo($nonVerifiedAdmin, VerifyEmail::class);

    $response->assertRedirect(route('admin.verification.notice'));
});
