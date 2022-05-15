<?php

namespace Tests\Feature\Admin\Auth;

use App\Models\Admin;
use App\Notifications\Admin\Auth\VerifyEmail;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\Auth\VerificationController
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
                'admin.auth:admin',
                'admin.verified',
            ])
            ->get('admin/verified', function () {
                return response('Accessed a resource that requires verification.');
            })
        ;
    }

    public function testVerificationIsNotRequiredIfAlreadyVerified()
    {
        $this->withoutExceptionHandling();

        $admin = Admin::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.verified'));

        $response->assertStatus(200);
    }

    public function testVerificationIsRequiredIfNotVerified()
    {
        $admin = Admin::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.verified'));

        $response->assertRedirect(route('admin.verification.notice'));
    }

    public function testVerificationIsRequiredIfNotVerifiedJson()
    {
        $admin = Admin::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->withHeader('Accept', 'application/json')
            ->get(route('admin.verified'))
        ;

        $response->assertStatus(403);
    }

    public function testCantVisitEmailVerificationNoticeWhenUnauthenticated()
    {
        $response = $this->get(route('admin.verification.notice'));

        $response->assertRedirect(route('admin.login'));
    }

    public function testCantVisitEmailVerificationNoticeWhenAlreadyVerified()
    {
        $admin = Admin::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.verification.notice'));

        $response->assertRedirect(route('admin.home'));
    }

    public function testCanVisitEmailVerificationWhenNotVerified()
    {
        $admin = Admin::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.verification.notice'));

        $response->assertStatus(200);

        $response->assertViewIs('admin.auth.verify');
    }

    public function testCantVisitEmailVerificationWhenUnauthenticated()
    {
        $admin = Admin::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->get($this->validVerificationVerifyRoute($admin));

        $response->assertRedirect(route('admin.login'));
    }

    public function testCantVisitEmailVerificationImpersonatingOtherUsers()
    {
        $admin_1 = Admin::factory()->create([
            'email_verified_at' => null,
        ]);

        $admin_2 = Admin::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($admin_1, 'admin')->get($this->validVerificationVerifyRoute($admin_2));

        $response->assertForbidden();

        static::assertFalse($admin_2->fresh()->hasVerifiedEmail());
    }

    public function testCantVisitEmailVerificationWhenVerified()
    {
        $admin = Admin::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'admin')->get($this->validVerificationVerifyRoute($admin));

        $response->assertRedirect(route('admin.home'));
    }

    public function testCantVerifyEmailWithInvalidSignature()
    {
        $admin = Admin::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'admin')->get($this->invalidVerificationVerifyRoute($admin));

        $response->assertStatus(403);
    }

    public function testCanVerifyEmailWithValidSignature()
    {
        $admin = Admin::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($admin, 'admin')->get($this->validVerificationVerifyRoute($admin));

        $response->assertRedirect(route('admin.home'));

        static::assertNotNull($admin->fresh()->email_verified_at);
    }

    public function testCantRequestResendEmailVerificationLinkWhenUnauthenticated()
    {
        $response = $this->post(route('admin.verification.resend'));

        $response->assertRedirect(route('admin.login'));
    }

    public function testCantVisitResendEmailVerificationWhenAlreadyVerified()
    {
        $admin = Admin::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'admin')->post(route('admin.verification.resend'));

        $response->assertRedirect(route('admin.home'));
    }

    public function testCanRequestResendEmailVerificationLink()
    {
        Notification::fake();
        $admin = Admin::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.verification.notice'))
            ->post(route('admin.verification.resend'))
        ;

        Notification::assertSentTo($admin, VerifyEmail::class);

        $response->assertRedirect(route('admin.verification.notice'));
    }

    protected function validVerificationVerifyRoute(Admin $admin)
    {
        return URL::temporarySignedRoute(
            'admin.verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $admin->id,
                'hash' => sha1($admin->email),
            ]
        );
    }

    protected function invalidVerificationVerifyRoute(Admin $admin)
    {
        return URL::temporarySignedRoute(
            'admin.verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $admin->id,
                'hash' => 'invalid-signature',
            ]
        );
    }
}
