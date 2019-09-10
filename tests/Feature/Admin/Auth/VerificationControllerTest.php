<?php

namespace Tests\Feature\Admin\Auth;

use App\Admin;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Admin\Auth\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @see \App\Http\Controllers\Admin\Auth\VerificationController
 */
class VerificationControllerTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_cant_visit_email_verification_notice_when_unauthenticated()
    {
        $response = $this->get(route('admin.verification.notice'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_cant_visit_email_verification_notice_when_already_verified()
    {
        $admin = factory(Admin::class)->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.verification.notice'));

        $response->assertRedirect(route('admin.home'));
    }

    public function test_can_visit_email_verification_when_not_verified()
    {
        $admin = factory(Admin::class)->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.verification.notice'));

        $response->assertStatus(200);

        $response->assertViewIs('admin.auth.verify');
    }

    public function test_cant_visit_email_verification_when_unauthenticated()
    {
        $admin = factory(Admin::class)->create([
            'email_verified_at' => null,
        ]);

        $response = $this->get($this->validVerificationVerifyRoute($admin));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_cant_visit_email_verification_impersonating_other_users()
    {
        $admin_1 = factory(Admin::class)->create([
            'email_verified_at' => null,
        ]);

        $admin_2 = factory(Admin::class)->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($admin_1, 'admin')->get($this->validVerificationVerifyRoute($admin_2));

        $response->assertForbidden();

        $this->assertFalse($admin_2->fresh()->hasVerifiedEmail());
    }

    public function test_cant_visit_email_verification_when_verified()
    {
        $admin = factory(Admin::class)->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'admin')->get($this->validVerificationVerifyRoute($admin));

        $response->assertRedirect(route('admin.home'));
    }

    public function test_cant_verify_email_with_invalid_signature()
    {
        $admin = factory(Admin::class)->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'admin')->get($this->invalidVerificationVerifyRoute($admin));

        $response->assertStatus(403);
    }

    public function test_can_verify_email_with_valid_signature()
    {
        $admin = factory(Admin::class)->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($admin, 'admin')->get($this->validVerificationVerifyRoute($admin));

        $response->assertRedirect(route('admin.home'));

        $this->assertNotNull($admin->fresh()->email_verified_at);
    }

    public function test_cant_request_resend_email_verification_link_when_unauthenticated()
    {
        $response = $this->post(route('admin.verification.resend'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_cant_visit_resend_email_verification_when_already_verified()
    {
        $admin = factory(Admin::class)->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'admin')->post(route('admin.verification.resend'));

        $response->assertRedirect(route('admin.home'));
    }

    public function test_can_request_resend_email_verification_link()
    {
        Notification::fake();
        $admin = factory(Admin::class)->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.verification.notice'))
            ->post(route('admin.verification.resend'));

        Notification::assertSentTo($admin, VerifyEmail::class);

        $response->assertRedirect(route('admin.verification.notice'));
    }
}