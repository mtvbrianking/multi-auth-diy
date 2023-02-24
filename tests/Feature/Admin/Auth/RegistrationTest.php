<?php

use App\Models\Admin;
use App\Providers\RouteServiceProvider;

test('registration screen can be rendered', function () {
    $response = $this->get('/admin/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/admin/register', [
        'name' => 'Test Admin',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticatedAs(Admin::first(), 'admin');
    $response->assertRedirect(RouteServiceProvider::ADMIN_HOME);
});
