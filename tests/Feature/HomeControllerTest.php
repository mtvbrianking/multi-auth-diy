<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\HomeController
 *
 * @internal
 * @coversNothing
 */
final class HomeControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testCanVisitLandingPageUnauthenticated()
    {
        $this->get(url('/'))->assertStatus(200);
    }

    public function testCanVisitHomeUnauthenticated()
    {
        $this->get(route('home'))->assertStatus(302)->assertRedirect(route('login'));
    }

    public function testCanVisitHomeIfAuthenticated()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertStatus(200);
    }
}
