<?php

namespace App\Filament\Resources\ReservationChecks\Pages;

use App\Filament\Resources\ReservationChecks\ReservationCheckResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListReservationChecks extends ListRecords
{
    protected static string $resource = ReservationCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('review')
                ->label('Start reviewing')
                ->icon('heroicon-o-play')
                ->badge(ReservationCheckResource::getNavigationBadge())
                ->url(ReservationCheckResource::getUrl('review')),
        ];
    }
}
