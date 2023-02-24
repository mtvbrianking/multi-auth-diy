<?php

use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

test('password can be updated', function () {
    $admin = Admin::factory()->create();

    $response = $this
        ->actingAs($admin, 'admin')
        ->from('/admin/profile')
        ->put('/admin/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/admin/profile');

    $this->assertTrue(Hash::check('new-password', $admin->refresh()->password));
});

test('correct password must be provided to update password', function () {
    $admin = Admin::factory()->create();

    $response = $this
        ->actingAs($admin, 'admin')
        ->from('/admin/profile')
        ->put('/admin/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('updatePassword', 'current_password')
        ->assertRedirect('/admin/profile');
});
