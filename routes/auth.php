<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('login')->name('login.')->group(function () {
    Route::get('redirect', [AuthController::class, 'redirect'])->name('login.redirect');
    Route::get('callback', [AuthController::class, 'callback'])->name('login.callback');
});

