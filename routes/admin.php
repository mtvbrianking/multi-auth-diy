<?php

Route::group(['namespace' => 'Admin'], function () {
    Route::get('/', 'HomeController@index')->name('admin.home');

    Route::get('/protected', function () {
        return response('Admins must confirm their password before reading this...');
    })->name('admin.password.protected')->middleware(['admin.auth', 'admin.password.confirm']);

    // Login
    Route::get('login', 'Auth\LoginController@showLoginForm')->name('admin.login');
    Route::post('login', 'Auth\LoginController@login');
    Route::post('logout', 'Auth\LoginController@logout')->name('admin.logout');

    // Register
    Route::get('register', 'Auth\RegisterController@showRegistrationForm')->name('admin.register');
    Route::post('register', 'Auth\RegisterController@register');

    // Reset Password
    Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('admin.password.request');
    Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('admin.password.email');
    Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('admin.password.reset');
    Route::post('password/reset', 'Auth\ResetPasswordController@reset')->name('admin.password.update');

    // Confirm Password
    Route::get('password/confirm', 'Auth\ConfirmPasswordController@showConfirmForm')->name('admin.password.confirm');
    Route::post('password/confirm', 'Auth\ConfirmPasswordController@confirm');

    // Verify Email
    Route::get('email/verify', 'Auth\VerificationController@show')->name('admin.verification.notice');
    Route::get('email/verify/{id}/{hash}', 'Auth\VerificationController@verify')->name('admin.verification.verify');
    Route::post('email/resend', 'Auth\VerificationController@resend')->name('admin.verification.resend');
});
