<?php

use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\UserController;
use App\Http\Middleware\ValidateKeycloakToken;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response()->json(['message' => 'pong']);
});


Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::get('/rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');
});

Route::prefix('v1')->name('api.v1.')->middleware([ValidateKeycloakToken::class])->group(function () {
    Route::get('/user', [UserController::class, 'show'])->name('user.show');
});
