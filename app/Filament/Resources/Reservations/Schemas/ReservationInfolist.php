<?php

namespace App\Filament\Resources\Reservations\Schemas;

use App\Filament\Resources\Users\UserResource;
use App\Models\Reservation;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ReservationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->placeholder('-'),
                TextEntry::make('room.name')
                    ->label('Room'),
                TextEntry::make('user.name')
                    ->label('User')
                    ->url(fn (Reservation $record) => $record->user && UserResource::canView($record->user)
                        ? UserResource::getUrl('view', ['record' => $record->user])
                        : null),
                TextEntry::make('start_at')
                    ->dateTime('d/m/y H:i'),
                TextEntry::make('end_at')
                    ->dateTime('H:i'),
                IconEntry::make('share_user')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->badge()
                    ->color(fn ($state) => $state->getColor()),
                TextEntry::make('organisation.name')
                    ->label('Organisation')
                    ->placeholder('-'),
            ]);
    }
}
