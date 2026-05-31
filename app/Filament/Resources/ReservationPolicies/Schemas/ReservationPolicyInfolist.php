<?php

namespace App\Filament\Resources\ReservationPolicies\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReservationPolicyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Grid::make(1)
                    ->schema([
                        TextEntry::make('role_name')
                            ->label('Role Name'),

                        TextEntry::make('max_days_in_advance')
                            ->label('Max Days in Advance')
                            ->suffix(' days'),

                        TextEntry::make('rooms.name')
                            ->label('Applicable Rooms')
                            ->badge()
                            ->placeholder('No rooms assigned'),

                        TextEntry::make('shieldRole.name')
                            ->label('Assigned Role')
                            ->placeholder('All Roles (Default)'),
                    ]),

                Section::make('Policy Schedule Entries')
                    ->description('Defined time brackets for this policy.')
                    ->compact()
                    ->schema([
                        RepeatableEntry::make('entries')
                            ->hiddenLabel()
                            ->columns(3)
                            ->schema([
                                TextEntry::make('day_of_week')
                                    ->hiddenLabel()
                                    ->formatStateUsing(fn($state) => match ((int)$state) {
                                        0 => 'Monday',
                                        1 => 'Tuesday',
                                        2 => 'Wednesday',
                                        3 => 'Thursday',
                                        4 => 'Friday',
                                        5 => 'Saturday',
                                        6 => 'Sunday',
                                        8 => 'All Week',
                                        default => 'Unknown'
                                    }),

                                TextEntry::make('start_time')
                                    ->hiddenLabel()
                                ,

                                TextEntry::make('end_time')
                                    ->hiddenLabel()
                                ,
                            ])
                    ])
            ]);
    }
}
