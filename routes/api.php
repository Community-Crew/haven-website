<?php

use App\Http\Controllers\Api\Agenda\AgendaIndexController;
use App\Http\Controllers\Api\Agenda\AgendaShowController;
use App\Http\Controllers\Api\Health\HealthShowController;
use App\Http\Controllers\Api\Media\MediaShowController;
use App\Http\Controllers\Api\PrivacyPolicy\PrivacyPolicyAcceptController;
use App\Http\Controllers\Api\PrivacyPolicy\PrivacyPolicyShowController;
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
use App\Http\Middleware\EnsureUserAcceptedPrivacyPolicy;
use App\Http\Middleware\EnsureUserIsActivated;
use App\Http\Middleware\ValidateKeycloakToken;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response()->json(['message' => 'pong']);
});

Route::get('/health', HealthShowController::class)->name('api.health');

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Rooms
    Route::get('/rooms', RoomIndexController::class)->name('rooms.index');
    Route::get('/rooms/{room}', RoomShowController::class)->name('rooms.show');

    // Agendas
    Route::get('/agendas', AgendaIndexController::class)->name('agendas.index');
    Route::get('/agendas/{agenda}', AgendaShowController::class)->name('agendas.show');

    // Media
    Route::get('/media/{media:uuid}', MediaShowController::class)->name('media.show');

    // Privacy Policy
    Route::get('/privacy-policy', PrivacyPolicyShowController::class)->name('privacy-policy.show');
});

Route::prefix('v1')->name('api.v1.')->middleware([ValidateKeycloakToken::class])->group(function () {
    // User
    Route::get('/user', UserController::class)->name('user.show');

    Route::post('/user/activate', ActivateAccountController::class)->name('user.activate');

    // Callable regardless of activation/acceptance status, so a user stuck
    // behind EnsureUserAcceptedPrivacyPolicy below can actually clear it.
    Route::post('/privacy-policy/accept', PrivacyPolicyAcceptController::class)->name('privacy-policy.accept');

    Route::middleware([EnsureUserAcceptedPrivacyPolicy::class, EnsureUserIsActivated::class])->group(function () {
        // Reservation
        Route::get('/reservations/me', ReservationController::class)->name('user.reservations.index');
        Route::post('/reservations', ReservationStoreController::class)->name('reservations.store')
            ->middleware('throttle:reservation-writes');
        Route::patch('/reservations/{reservation}/cancel', ReservationCancelController::class)->name('reservations.cancel')
            ->middleware('throttle:reservation-writes');
        Route::put('/reservations/{reservation}', [ReservationUpdateController::class, '__invoke'])->name('reservations.update')
            ->middleware('throttle:reservation-writes');

        // Rooms
        Route::get('/rooms/{room:id}/reservations',
            RoomReservationIndexController::class)->name('rooms.reservations.index')
            ->middleware('throttle:reservation-reads');
        Route::get('/rooms/{room:id}/weekly-schedule', RoomPolicyController::class)->name('rooms.policy.index')
            ->middleware('throttle:reservation-reads');
    });
});
