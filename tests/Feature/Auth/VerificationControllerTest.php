<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Auth\VerificationController
 */
final class VerificationControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Route::name('admin.verified')
            ->middleware([
                'web',
                'auth',
                'verified',
            ])
            ->get('admin/verified', function () {
                return response('Accessed a resource that requires verification.');
            })
        ;
    }

    public function testCantVisitEmailVerificationNoticeWhenUnauthenticated()
    {
        $response = $this->get(route('verification.notice'));

        $response->assertRedirect(route('login'));
    }

    public function testCantVisitEmailVerificationNoticeWhenAlreadyVerified()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertRedirect(route('home'));
    }

    public function testCanVisitEmailVerificationWhenNotVerified()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertStatus(200);

        $response->assertViewIs('auth.verify');
    }

    public function testCantVisitEmailVerificationWhenUnauthenticated()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->get($this->validVerificationVerifyRoute($user));

        $response->assertRedirect(route('login'));
    }

    public function testCantVisitEmailVerificationImpersonatingOtherUsers()
    {
        $user_1 = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $user_2 = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user_1)->get($this->validVerificationVerifyRoute($user_2));

        $response->assertForbidden();

        static::assertFalse($user_2->fresh()->hasVerifiedEmail());
    }

    public function testCantVisitEmailVerificationWhenVerified()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get($this->validVerificationVerifyRoute($user));

        $response->assertRedirect(route('home'));
    }

    public function testCantVerifyEmailWithInvalidSignature()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get($this->invalidVerificationVerifyRoute($user));

        $response->assertStatus(403);
    }

    public function testCanVerifyEmailWithValidSignature()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get($this->validVerificationVerifyRoute($user));

        $response->assertRedirect(route('home'));

        static::assertNotNull($user->fresh()->email_verified_at);
    }

    public function testCantRequestResendEmailVerificationLinkWhenUnauthenticated()
    {
        $response = $this->post(route('verification.resend'));

        $response->assertRedirect(route('login'));
    }

    public function testCantVisitResendEmailVerificationWhenAlreadyVerified()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('verification.resend'));

        $response->assertRedirect(route('home'));
    }

    public function testCanRequestResendEmailVerificationLink()
    {
        Notification::fake();
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->from(route('verification.notice'))
            ->post(route('verification.resend'))
        ;

        Notification::assertSentTo($user, VerifyEmail::class);

        $response->assertRedirect(route('verification.notice'));
    }

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
}
