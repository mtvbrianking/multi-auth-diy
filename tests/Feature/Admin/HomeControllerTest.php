<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\HomeController
 */
class HomeControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_cant_visit_home_unauthenticated()
    {
        $response = $this->get(route('admin.home'));
        $response->assertStatus(302);
        $response->assertRedirect(route('admin.login'));
    }

    public function test_can_visit_home_if_authenticated()
    {
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')->get(route('admin.home'));

        $response->assertStatus(200);
    }

    public function test_can_non_admins_cant_access_dashboard()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'web')->get(route('admin.home'));

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.login'));
    }
}
