<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', []);
})->name('home');

Route::get('test', function () {
    return Inertia::render('auth/Validation');
})->name('Test');



require __DIR__.'/auth.php';
require __DIR__.'/onboarding.php';
