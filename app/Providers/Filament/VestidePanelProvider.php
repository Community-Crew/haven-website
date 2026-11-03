<?php

namespace App\Providers\Filament;

use App\Http\Middleware\AuthenticateAdminWithKeycloak;
use App\Http\Middleware\UseDutchLocale;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * The landlord's own small panel at /vestide on the admin domain, in Dutch:
 * how the hangouts were left after each reservation, looking up a resident's
 * track record by name, the monthly mutaties import and issuing registration
 * codes. Deliberately no browsable list of residents - see ResidentRecords.
 *
 * Kept separate from the admin panel so Vestide staff can't end up anywhere
 * else, whatever permissions get handed out there - access is the Vestide
 * role alone (see User::canAccessPanel()). Same Keycloak login as the admin
 * panel.
 */
class VestidePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('vestide')
            ->domain(config('services.admin_domain'))
            ->path('vestide')
            ->brandName('Haven × Vestide')
            ->colors([
                'primary' => Color::Teal,
            ])
            ->discoverPages(in: app_path('Filament/Vestide/Pages'), for: 'App\Filament\Vestide\Pages')
            ->discoverWidgets(in: app_path('Filament/Vestide/Widgets'), for: 'App\Filament\Vestide\Widgets')
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
            ->middleware([
                UseDutchLocale::class,
            ], isPersistent: true)
            ->authMiddleware([
                AuthenticateAdminWithKeycloak::class,
            ])
            ->userMenuItems([
                Action::make('adminPanel')
                    ->label('Naar het Haven-beheer')
                    ->icon('heroicon-o-arrows-right-left')
                    ->url(fn () => Filament::getPanel('admin')->getUrl())
                    ->visible(fn () => (bool) auth()->user()?->isSuperAdmin()),
            ]);
    }
}
