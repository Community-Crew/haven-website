<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\MembershipsOverview;
use App\Filament\Widgets\OverRegistrationOverview;
use App\Filament\Widgets\PendingActionsOverview;
use App\Filament\Widgets\SentMailOverview;
use App\Filament\Widgets\UpcomingReservations;
use App\Http\Controllers\Auth\KeycloakAdminController;
use App\Http\Middleware\AuthenticateAdminWithKeycloak;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->domain(config('services.admin_domain'))
            ->path('')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                PendingActionsOverview::class,
                OverRegistrationOverview::class,
                MembershipsOverview::class,
                SentMailOverview::class,
                UpcomingReservations::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                AuthenticateAdminWithKeycloak::class,
            ])
            ->strictAuthorization()
            // Filament defaults every relation manager on a resource's View
            // page to read-only, hiding Create/Edit/Delete regardless of the
            // viewer's actual permissions. This app manages nested data
            // (memberships, board positions, reservation checks/notes, ...)
            // from exactly those View pages - there's no separate page for
            // them - so the per-model policies already in place should be
            // the only thing gating those actions.
            ->readOnlyRelationManagersOnResourceViewPagesByDefault(false)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->routes(function () {
                Route::get('/oauth/keycloak/redirect', [KeycloakAdminController::class, 'redirect'])
                    ->name('oauth.keycloak.redirect');

                Route::get('/oauth/keycloak/callback', [KeycloakAdminController::class, 'callback'])
                    ->name('oauth.keycloak.callback');
            })
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            // Daily-use groups (Members, Bookings, Agenda) are left expanded;
            // everything touched less often defaults collapsed so the
            // sidebar opens onto what admins actually need day to day
            // instead of a full wall of options.
            ->navigationGroups([
                'Members',
                'Bookings',
                'Agenda',
                NavigationGroup::make('Board & Commissions')->collapsed(),
                NavigationGroup::make('Facilities')->collapsed(),
                NavigationGroup::make('Property')->collapsed(),
                NavigationGroup::make('Content')->collapsed(),
                NavigationGroup::make('Communication')->collapsed(),
                NavigationGroup::make('System')->collapsed(),
                NavigationGroup::make(__('filament-shield::filament-shield.nav.group'))->collapsed(),
            ]);
    }
}
