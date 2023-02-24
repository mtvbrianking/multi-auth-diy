<?php

use App\Models\Admin;

test('profile page is displayed', function () {
    $admin = Admin::factory()->create();

    $response = $this
        ->actingAs($admin, 'admin')
        ->get('/admin/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $admin = Admin::factory()->create();

    $response = $this
        ->actingAs($admin, 'admin')
        ->patch('/admin/profile', [
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/admin/profile');

    $admin->refresh();

    $this->assertSame('Test Admin', $admin->name);
    $this->assertSame('admin@example.com', $admin->email);
    $this->assertNull($admin->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $admin = Admin::factory()->create();

    $response = $this
        ->actingAs($admin, 'admin')
        ->patch('/admin/profile', [
            'name' => 'Test Admin',
            'email' => $admin->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/admin/profile');

    $this->assertNotNull($admin->refresh()->email_verified_at);
});

test('user can delete their account', function () {
    $admin = Admin::factory()->create();

    $response = $this
        ->actingAs($admin, 'admin')
        ->delete('/admin/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/admin');

    $this->assertGuest();
    $this->assertNull($admin->fresh());
});

test('correct password must be provided to delete account', function () {
    $admin = Admin::factory()->create();

    $response = $this
        ->actingAs($admin, 'admin')
        ->from('/admin/profile')
        ->delete('/admin/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/admin/profile');

    $this->assertNotNull($admin->fresh());
});
