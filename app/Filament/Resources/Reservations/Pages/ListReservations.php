<?php

namespace App\Filament\Resources\Reservations\Pages;

use App\Filament\Resources\Reservations\ReservationResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListReservations extends ListRecords
{
    protected static string $resource = ReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('agenda')
                ->label('Agenda')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->color('gray')
                ->url(ReservationResource::getUrl('index')),
            CreateAction::make(),
        ];
    }
}
