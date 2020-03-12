<?php

namespace Tests\Feature\Admin;

use App\Admin;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\HomeController
 */
class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_cant_visit_home_unauthenticated()
    {
        $response = $this->get(route('admin.home'));
        $response->assertStatus(302);
        $response->assertRedirect(route('admin.login'));
    }

    public function test_can_visit_home_if_authenticated()
    {
        $admin = factory(Admin::class)->create();

        $response = $this->actingAs($admin, 'admin')->get(route('admin.home'));

        $response->assertStatus(200);
    }

    public function test_can_non_admins_cant_access_dashboard()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user, 'web')->get(route('admin.home'));

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.login'));
    }

    public function test_can_admins_cant_access_default_user_side()
    {
        $admin = factory(Admin::class)->create();

        $response = $this->actingAs($admin, 'admin')->get(route('home'));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }
}
