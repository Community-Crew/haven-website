<?php

namespace App\Filament\Support;

use App\Filament\Resources\ReservationChecks\ReservationCheckResource;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

/**
 * Reservations and their check-in/out photos are one place in the admin:
 * these header actions lead from the agenda and table to the photo review.
 */
class ReservationPageActions
{
    public static function reviewPhotos(): Action
    {
        return Action::make('reviewPhotos')
            ->label('Review photos')
            ->icon(Heroicon::OutlinedCamera)
            ->color('gray')
            ->badge(fn () => ReservationCheckResource::getNavigationBadge())
            ->badgeColor('warning')
            ->visible(fn () => ReservationCheckResource::canViewAny() && ReservationCheckResource::getNavigationBadge() !== null)
            ->url(fn () => ReservationCheckResource::getUrl('review'));
    }

    public static function allPhotos(): Action
    {
        return Action::make('allPhotos')
            ->label('All photos')
            ->icon(Heroicon::OutlinedPhoto)
            ->color('gray')
            ->visible(fn () => ReservationCheckResource::canViewAny())
            ->url(fn () => ReservationCheckResource::getUrl('index'));
    }
}
