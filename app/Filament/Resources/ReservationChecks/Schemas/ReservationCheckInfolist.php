<?php

namespace App\Filament\Resources\ReservationChecks\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ReservationCheckInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                ImageEntry::make('photo_urls')
                    ->label('Photos')
                    ->columnSpanFull(),
                TextEntry::make('reservation.name')
                    ->label('Reservation'),
                TextEntry::make('reservation.room.name')
                    ->label('Room'),
                TextEntry::make('reservation.user.name')
                    ->label('Resident'),
                TextEntry::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->getLabel()),
                TextEntry::make('occurred_at')
                    ->dateTime(),
                TextEntry::make('flag_reason')
                    ->label('Automated flag')
                    ->placeholder('Not flagged'),
                TextEntry::make('verification_status')
                    ->badge()
                    ->color(fn ($state) => $state->getColor())
                    ->formatStateUsing(fn ($state) => $state->getLabel()),
                TextEntry::make('reviewer.name')
                    ->label('Reviewed by')
                    ->placeholder('-'),
                TextEntry::make('reviewed_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('rejection_note')
                    ->label('Rejection note')
                    ->placeholder('-')
                    ->columnSpanFull(),
            ]);
    }
}
