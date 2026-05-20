<?php

use App\Http\Controllers\Admin\AgendaController;
use App\Http\Controllers\Admin\AgendaItemController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrganisationController;
use App\Http\Controllers\Admin\RegistrationCodeController;
use App\Http\Controllers\Admin\ReservationController;
use App\Http\Controllers\Admin\ReservationPolicyController;
use App\Http\Controllers\Admin\ReservationPolicyEntryController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

Route::prefix('admin')->middleware(['auth', 'role:view-dashboard'])->name('admin.')->group(function () {
    Route::get('', [DashboardController::class, 'index'])->name('index');
    Route::Resource('units', UnitController::class);
    Route::Resource('registration-codes', RegistrationCodeController::class)->only(['store', 'destroy', 'show', 'index']);
    Route::get('registration-codes.print.{registrationCode}', [RegistrationCodeController::class, 'print'])->name('registration-codes.print');
    Route::Resource('reservations', ReservationController::class);
    Route::Resource('rooms', RoomController::class);
    Route::Resource('organisations', OrganisationController::class);
    Route::delete('organisations.{organisation}.users.{user}', [OrganisationController::class, 'detachUser'])->name('organisations.users.detach');
    Route::Resource('agendas', AgendaController::class);
    Route::Resource('agendas.items', AgendaItemController::class)->parameters([
        'items' => 'agendaItem:slug',
    ]);
    Route::Resource('reservation-policies', ReservationPolicyController::class);
    Route::Resource('reservation-policies.entries', ReservationPolicyEntryController::class);
    Route::Resource('users', UserController::class);


});

Route::domain(env('ADMIN_APP_URL'))->group(function () {
    Route::get('login/redirect', function () {
        return Socialite::driver('keycloak')->redirect();
    })->name('admin.login.redirect');

    Route::get('/login/callback', function () {
        $keycloakUser = Socialite::driver('keycloak')->user();

        $user = User::updateOrCreate(
            ['email' => $keycloakUser->getEmail()],
            [
                'name' => $keycloakUser->getName(),
                'keycloak_id' => $keycloakUser->getId(),
                'keycloak_token' => $keycloakUser->token,
            ]
        );

        $payload = json_decode(base64_decode(explode('.', $keycloakUser->token)[1]), true);
        session(['keycloak_groups' => $payload['groups'] ?? []]);

        auth()->login($user);

        return redirect(env('ADMIN_APP_URL'));
    })->name('admin.login.callback');

    Route::post('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        $baseUrl = rtrim(config('services.keycloak.base_url'), '/');
        $realm = config('services.keycloak.realm');
        $postLogoutRedirectUri = 'http://admin.havencommunity.test';

        $keycloakLogoutUrl = "{$baseUrl}/realms/{$realm}/protocol/openid-connect/logout" .
            '?' . http_build_query([
                'client_id' => config('services.keycloak.client_id'),
                'post_logout_redirect_uri' => $postLogoutRedirectUri,
            ]);

        return redirect()->away($keycloakLogoutUrl);
    })->name('filament.admin.auth.logout');
});
