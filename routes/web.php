<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', []);
})->name('home');

Route::get('test', function () {
    return Inertia::render('auth/Validation');
})->name('Test');

Route::get('profile', [ProfileController::class, 'index'])->name('profile');


require __DIR__.'/auth.php';
require __DIR__.'/onboarding.php';
