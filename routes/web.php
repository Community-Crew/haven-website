<?php

use App\Http\Controllers\Public\ProfileController;
use App\Http\Controllers\Public\ReservationController;
use App\Http\Controllers\Public\RoomController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home', []);
})->name('home');

Route::resource('rooms', RoomController::class)->only(['index', 'show']);

Route::middleware(['auth'])->group(function () {
    Route::get('profile', [ProfileController::class, 'index'])->middleware(['auth'])->name('profile');
});

Route::get('images/placeholder', function () {
    $url = Cache::remember('hetzner_placeholder_url', 50 * 60, function () {
        return Storage::disk('hetzner')->temporaryUrl(
            'placeholder.gif',
            now()->addMinutes(60)
        );
    });

    return redirect($url);
})->name('image.placeholder');

Route::get('privacy-policy', function () {
    return Inertia::render('PrivacyPolicy');
})->name('privacy-policy');

route::get('wip', function () {
    return Inertia::render('WIP', []);
})->name('wip');

route::Resource('reservations', ReservationController::class)->only(['store', 'destroy', 'update']);

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
