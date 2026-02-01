<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrganisationController;
use App\Http\Controllers\Admin\RegistrationCodeController;
use App\Http\Controllers\Admin\ReservationController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\UnitController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware(['auth', 'role:view-dashboard'])->name('admin.')->group(function () {
    Route::get('', [DashboardController::class, 'index'])->name('index');
    Route::Resource('units', UnitController::class);
    Route::Resource('registration-codes', RegistrationCodeController::class)->only(['store', 'destroy', 'show', 'index']);
    Route::get('registration-codes/print/{registrationCode}', [RegistrationCodeController::class, 'print'])->name('registration-codes.print');
    Route::Resource('reservations', ReservationController::class);
    Route::Resource('rooms', RoomController::class);
    Route::Resource('organisations', OrganisationController::class);
    Route::delete('organisations/{organisation}/users/{user}', [OrganisationController::class, 'detachUser'])->name('organisations.users.detach');
});
