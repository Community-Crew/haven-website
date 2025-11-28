<?php

use App\Http\Controllers\AdminDashboard\AdminDashboardController;
use App\Http\Controllers\AdminDashboard\UnitController;
use App\Http\Controllers\RegistrationCodeController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware(['auth', 'role:view-dashboard'])->name('admin.')->group(function () {
    Route::get('', [AdminDashboardController::class, 'index'])->name('index');
    Route::Resource('units', UnitController::class);
    Route::Resource('registration-codes', RegistrationCodeController::class)->only(['store', 'destroy', 'show']);
    Route::get('registration-codes/print/{registrationCode}', [RegistrationCodeController::class, 'print'])->name('registration-codes.print');
});
