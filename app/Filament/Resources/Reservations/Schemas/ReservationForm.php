<?php

namespace App\Filament\Resources\Reservations\Schemas;

use App\Enums\ReservationStatus;
use App\Models\Unit;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Schema;

class ReservationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextInput::make('name'),
                Select::make('room_id')
                    ->relationship('room', 'name')
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->getOptionLabelFromRecordUsing(fn(User $record) => "{$record->name} ({$record->email})")
                    ->searchable()
                    ->required(),
                DateTimePicker::make('start_at')
                    ->required(),
                DateTimePicker::make('end_at')
                    ->required(),
                Toggle::make('share_user')
                    ->inline(false)
                    ->required(),
                ToggleButtons::make('status')
                    ->options(function () {
                        return collect(ReservationStatus::cases())
                            ->mapWithKeys(fn($status) => [$status->getValue() => $status->getLabel()])
                            ->all();
                    })
                    ->default(ReservationStatus::PENDING->getValue())
                    ->inline()
                    ->colors(
                        collect(ReservationStatus::cases())
                            ->mapWithKeys(fn($status) => [$status->value => $status->getColor()])
                            ->all()
                    )
                    ->required(),
                Select::make('organisation_id')
                    ->relationship('organisation', 'name'),
            ]);
    }
}
