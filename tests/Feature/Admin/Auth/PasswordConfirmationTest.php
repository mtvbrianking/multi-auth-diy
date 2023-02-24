<?php

use App\Models\Admin;

test('confirm password screen can be rendered', function () {
    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admin')->get('/admin/confirm-password');

    $response->assertStatus(200);
});

test('password can be confirmed', function () {
    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admin')->post('/admin/confirm-password', [
        'password' => 'password',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
});

test('password is not confirmed with invalid password', function () {
    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admin')->post('/admin/confirm-password', [
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors();
});
