<?php

namespace App\Filament\Widgets;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UpcomingReservations extends TableWidget
{
    use HasWidgetShield;

    protected static ?string $heading = 'Upcoming Reservations';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Reservation::query()
                    ->where('status', ReservationStatus::APPROVED)
                    ->where('start_at', '>=', now())
                    ->orderBy('start_at')
            )
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('room.name')
                    ->label('Room'),
                TextColumn::make('user.name')
                    ->label('By'),
                TextColumn::make('start_at')
                    ->label('Starts')
                    ->dateTime('d/m/y H:i'),
            ])
            ->paginated([5]);
    }
}
