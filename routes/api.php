<?php

use App\Http\Controllers\Api\Agenda\AgendaIndexController;
use App\Http\Controllers\Api\Reservation\ReservationCancelController;
use App\Http\Controllers\Api\Reservation\ReservationStoreController;
use App\Http\Controllers\Api\Reservation\ReservationUpdateController;
use App\Http\Controllers\Api\Room\RoomIndexController;
use App\Http\Controllers\Api\Room\RoomPolicyController;
use App\Http\Controllers\Api\Room\RoomReservationIndexController;
use App\Http\Controllers\Api\Room\RoomShowController;
use App\Http\Controllers\Api\User\ActivateAccountController;
use App\Http\Controllers\Api\User\ReservationController;
use App\Http\Controllers\Api\User\UserController;
use App\Http\Middleware\EnsureUserIsActivated;
use App\Http\Middleware\ValidateKeycloakToken;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response()->json(['message' => 'pong']);
});

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Rooms
    Route::get('/rooms', RoomIndexController::class)->name('rooms.index');
    Route::get('/rooms/{room}', RoomShowController::class)->name('rooms.show');

    // Agendas
    Route::get('/agendas', AgendaIndexController::class)->name('agendas.index');
});

Route::prefix('v1')->name('api.v1.')->middleware([ValidateKeycloakToken::class])->group(function () {
    // User
    Route::get('/user', UserController::class)->name('user.show');

    Route::post('/user/activate', ActivateAccountController::class)->name('user.activate');

    Route::middleware([EnsureUserIsActivated::class])->group(function () {
        // Reservation
        Route::get('/reservations/me', ReservationController::class)->name('user.reservations.index');
        Route::post('/reservations', ReservationStoreController::class)->name('reservations.store');
        Route::patch('/reservations/{reservation}/cancel', ReservationCancelController::class)->name('reservations.cancel');
        Route::put('/reservations/{reservation}', [ReservationUpdateController::class, '__invoke'])->name('reservations.update');

        // Rooms
        Route::get('/rooms/{room:id}/reservations',
            RoomReservationIndexController::class)->name('rooms.reservations.index');
        Route::get('/rooms/{room:id}/weekly-schedule', RoomPolicyController::class)->name('rooms.policy.index');
    });
});
