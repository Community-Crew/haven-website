<?php

use App\Http\Controllers\Auth\AccountValidationController;
use App\Http\Controllers\Auth\KeycloakLoginController;
use App\Http\Controllers\Auth\LogoutController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('auth.')->group(function () {
    Route::prefix('login')->name('login.')->group(function () {
        Route::get('redirect', [KeycloakLoginController::class, 'redirect'])->name('redirect');
        Route::get('callback', [KeycloakLoginController::class, 'callback'])->name('callback');
    });
    Route::get('register', [KeycloakLoginController::class, 'register'])->name('register');
    Route::post('validate', [AccountValidationController::class, 'store'])->name('validate');
    Route::get('logout', [LogoutController::class, 'logout'])->name('logout');
});

