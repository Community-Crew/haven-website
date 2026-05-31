<?php

namespace App\Filament\Resources\ReservationPolicies\Schemas;

use App\Services\TimeConverterService;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class ReservationPolicyForm
{
    public static function configure(Schema $schema): Schema
    {
        $generateTimeOptions = function () {
            $options = [];

            for ($minutes = 0; $minutes < 1440; $minutes += 30) {
                $hours = floor($minutes / 60);
                $mins = $minutes % 60;

                $options[$minutes] = sprintf('%02d:%02d', $hours, $mins);
            }

            $options[1440] = '24:00';

            return $options;
        };

        return $schema
            ->components([
                Grid::make(1)
                    ->schema([
                        TextInput::make('role_name')
                            ->label('Role Name')
                            ->required(),
                        TextInput::make('max_days_in_advance')
                            ->label('Max Days in Advance')
                            ->required()
                            ->numeric(),
                        Select::make('rooms')
                            ->relationship('rooms', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->label('Applicable Rooms')
                            ->placeholder('Select rooms for this policy...'),
                        Select::make('shield_role_id')
                            ->label('Assign to Role')
                            ->relationship('shieldRole', 'name')
                            ->placeholder('All Roles (Default)')
                            ->searchable()
                            ->preload(),
                    ]),

                Section::make('Policy Schedule Entries')
                    ->heading('Policy Schedule Entries')
                    ->description('Define time brackets.')
                    ->compact()
                    ->schema([
                        Repeater::make('entries')
                            ->relationship(
                                name: 'entries',
                                modifyQueryUsing: fn($query) => $query->orderBy('day_of_week')->orderBy('start_time')
                            )
                            ->hiddenLabel()
                            ->collapsible()
                            ->collapsed()
                            ->defaultItems(0)
                            ->itemLabel(fn(array $state): ?string => isset($state['day_of_week']) ? match ((int)$state['day_of_week']) {
                                0 => 'Monday Slot',
                                1 => 'Tuesday Slot',
                                2 => 'Wednesday Slot',
                                3 => 'Thursday Slot',
                                4 => 'Friday Slot',
                                5 => 'Saturday Slot',
                                6 => 'Sunday Slot',
                                8 => 'All Week Slot',
                                default => 'Schedule Slot'
                            } : null
                            )
                            ->schema([
                                Select::make('day_of_week')
                                    ->label('Day of Week')
                                    ->options([
                                        0 => 'Monday',
                                        1 => 'Tuesday',
                                        2 => 'Wednesday',
                                        3 => 'Thursday',
                                        4 => 'Friday',
                                        5 => 'Saturday',
                                        6 => 'Sunday',
                                        8 => 'All Week',
                                    ])
                                    ->required(),

                                Select::make('start_time')
                                    ->label('Start Time')
                                    ->options($generateTimeOptions())
                                    ->required()
                                    ->searchable()
                                    ->formatStateUsing(function ($state) {
                                        if (is_null($state)) return null;
                                        if (is_int($state)) return $state;

                                        return TimeConverterService::toMinutes((string)$state);
                                    }),

                                Select::make('end_time')
                                    ->label('End Time')
                                    ->options($generateTimeOptions())
                                    ->required()
                                    ->searchable()
                                    ->formatStateUsing(function ($state) {
                                        if (is_null($state)) return null;
                                        if (is_int($state)) return $state;
                                        return TimeConverterService::toMinutes((string)$state);
                                    })
                                    ->rules([
                                        fn(Get $get): array => [
                                            function (string $attribute, $value, \Closure $fail) use ($get) {
                                                $startTime = $get('start_time');

                                                if (blank($startTime) || blank($value)) {
                                                    return;
                                                }

                                                if ($value <= $startTime) {
                                                    $fail('The end time must be later than the start time.');
                                                }
                                            },
                                        ],
                                    ]),

                            ])
                            ->columns(3)
                    ])
            ]);
    }
}
