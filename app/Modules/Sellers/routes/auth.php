<?php

use App\Modules\Sellers\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Modules\Sellers\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Modules\Sellers\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Modules\Sellers\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Modules\Sellers\Http\Controllers\Auth\NewPasswordController;
use App\Modules\Sellers\Http\Controllers\Auth\PasswordController;
use App\Modules\Sellers\Http\Controllers\Auth\PasswordResetLinkController;
use App\Modules\Sellers\Http\Controllers\Auth\RegisteredSellerController;
use App\Modules\Sellers\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::group(['as' => 'seller.', 'prefix' => '/seller', 'middleware' => ['web', 'seller.guest']], function () {
    Route::get('register', [RegisteredSellerController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredSellerController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::group(['as' => 'seller.', 'prefix' => '/seller', 'middleware' => ['web', 'seller.auth']], function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
