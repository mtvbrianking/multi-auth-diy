<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\HomeController
 */
final class HomeControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testCantVisitHomeUnauthenticated()
    {
        $response = $this->get(route('admin.home'));
        $response->assertStatus(302);
        $response->assertRedirect(route('admin.login'));
    }

    public function testCanVisitHomeIfAuthenticated()
    {
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')->get(route('admin.home'));

        $response->assertStatus(200);
    }

    public function testCanNonAdminsCantAccessDashboard()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'web')->get(route('admin.home'));

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.login'));
    }
}
