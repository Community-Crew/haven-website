<?php

use App\Http\Controllers\ProfileController;
use App\Http\Middleware\CheckRole;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', []);
})->name('home');

Route::get('test', function () {
    return Inertia::render('auth/Validation');
})->name('Test');

Route::middleware(['auth'])->group(function () {
    Route::get('profile', [ProfileController::class, 'index'])->middleware(['auth'])->name('profile');
});


require __DIR__.'/auth.php';
require __DIR__.'/onboarding.php';
