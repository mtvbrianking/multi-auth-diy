<?php

namespace Tests\Feature\Auth;

use App\User;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @see \App\Http\Controllers\Auth\VerificationController
 */
class VerificationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function validVerificationVerifyRoute(User $user)
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );
    }

    protected function invalidVerificationVerifyRoute(User $user)
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $user->id,
                'hash' => 'invalid-signature',
            ]
        );
    }

    public function test_cant_visit_email_verification_notice_when_unauthenticated()
    {
        $response = $this->get(route('verification.notice'));

        $response->assertRedirect(route('login'));
    }

    public function test_cant_visit_email_verification_notice_when_already_verified()
    {
        $user = factory(User::class)->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertRedirect(route('home'));
    }

    public function test_can_visit_email_verification_when_not_verified()
    {
        $user = factory(User::class)->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertStatus(200);

        $response->assertViewIs('auth.verify');
    }

    public function test_cant_visit_email_verification_when_unauthenticated()
    {
        $user = factory(User::class)->create([
            'email_verified_at' => null,
        ]);

        $response = $this->get($this->validVerificationVerifyRoute($user));

        $response->assertRedirect(route('login'));
    }

    public function test_cant_visit_email_verification_impersonating_other_users()
    {
        $user_1 = factory(User::class)->create([
            'email_verified_at' => null,
        ]);

        $user_2 = factory(User::class)->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user_1)->get($this->validVerificationVerifyRoute($user_2));

        $response->assertForbidden();

        $this->assertFalse($user_2->fresh()->hasVerifiedEmail());
    }

    public function test_cant_visit_email_verification_when_verified()
    {
        $user = factory(User::class)->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get($this->validVerificationVerifyRoute($user));

        $response->assertRedirect(route('home'));
    }

    public function test_cant_verify_email_with_invalid_signature()
    {
        $user = factory(User::class)->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get($this->invalidVerificationVerifyRoute($user));

        $response->assertStatus(403);
    }

    public function test_can_verify_email_with_valid_signature()
    {
        $user = factory(User::class)->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get($this->validVerificationVerifyRoute($user));

        $response->assertRedirect(route('home'));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_cant_request_resend_email_verification_link_when_unauthenticated()
    {
        $response = $this->post(route('verification.resend'));

        $response->assertRedirect(route('login'));
    }

    public function test_cant_visit_resend_email_verification_when_already_verified()
    {
        $user = factory(User::class)->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('verification.resend'));

        $response->assertRedirect(route('home'));
    }

    public function test_can_request_resend_email_verification_link()
    {
        Notification::fake();
        $user = factory(User::class)->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->from(route('verification.notice'))
            ->post(route('verification.resend'));

        Notification::assertSentTo($user, VerifyEmail::class);

        $response->assertRedirect(route('verification.notice'));
    }
}
