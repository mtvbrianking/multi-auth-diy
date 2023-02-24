<?php

use App\Modules\Sellers\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'seller.auth', 'seller.verified'])->get('/seller', function () {
    return view('seller::dashboard');
})->name('seller.dashboard');

Route::group(['as' => 'seller.', 'prefix' => '/seller', 'middleware' => ['web', 'seller.auth']], function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
