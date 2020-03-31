<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\HomeController
 */
class HomeControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_can_visit_landing_page_unauthenticated()
    {
        $this->get(url('/'))->assertStatus(200);
    }

    public function test_can_visit_home_unauthenticated()
    {
        $this->get(route('home'))->assertStatus(302)->assertRedirect(route('login'));
    }

    public function test_can_visit_home_if_authenticated()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertStatus(200);
    }
}
