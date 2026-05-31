<?php

namespace App\Filament\Resources\Units\Tables;

use App\Models\Unit;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UnitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('building')
                    ->searchable(),
                TextColumn::make('floor')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('unit')
                    ->searchable(),
                TextColumn::make('subunit')
                    ->searchable(),
                TextColumn::make('max_residents')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('users_count')
                    ->label('Occupancy')
                    ->counts('users')
                    ->formatStateUsing(function (string $state, Unit $record) {
                        return "{$state} / {$record->max_residents}";
                    })
                    ->weight('bold')
                    ->color(function (string $state, Unit $record): string {
                        if ((int)$state > $record->max_residents) {
                            return 'danger';
                        }

                        return 'gray';
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('location_filter')
                    ->indicator('Location')
                    ->schema([
                        Select::make('building')
                            ->options([
                                'Castor' => 'Castor',
                                'Pollux' => 'Pollux',
                                'Terra' => 'Terra',
                            ])
                            ->reactive()
                            ->afterStateUpdated(fn(callable $set) => $set('floor', null)),

                        Select::make('floor')
                            ->placeholder(fn(callable $get) => $get('building') ? 'Select floor...' : 'Choose a building first')
                            ->disabled(fn(callable $get) => !$get('building'))
                            ->options(function (callable $get) {
                                $building = $get('building');

                                if (!$building) {
                                    return [];
                                }

                                if ($building === 'Terra') {
                                    return [
                                        '61' => '61',
                                        '62' => '62',
                                        '63' => '63',
                                        '64' => '64',
                                        '65' => '65',
                                    ];
                                }

                                return [
                                    '0' => 'Floor 0',
                                    '1' => 'Floor 1',
                                    '2' => 'Floor 2',
                                    '3' => 'Floor 3',
                                    '4' => 'Floor 4',
                                    '5' => 'Floor 5',
                                    '6' => 'Floor 6',
                                    '7' => 'Floor 7',
                                    '8' => 'Floor 8',
                                    '9' => 'Floor 9',
                                    '10' => 'Floor 10',
                                    '11' => 'Floor 11',
                                    '12' => 'Floor 12',
                                    '13' => 'Floor 13',
                                    '14' => 'Floor 14',
                                ];
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['building'],
                                fn(Builder $query, $building) => $query->where('building', $building)
                            )
                            ->when(
                                $data['floor'] !== null && $data['floor'] !== '',
                                fn(Builder $query, $floor) => $query->where('floor', $data['floor'])
                            );
                    }),
                SelectFilter::make('occupancy_status')
                    ->label('Occupancy Status')
                    ->options([
                        'vacant' => 'Vacant (Not Used)',
                        'under' => 'Under Capacity',
                        'full' => 'At Capacity',
                        'over' => 'Overoccupied',
                    ])
                    ->multiple()
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'], function ($query, $value) {
                            switch ($value) {
                                case 'vacant':
                                    return $query->whereDoesntHave('users');
                                case 'under':
                                    return $query->whereIn('id', fn($q) => $q->select('unit_id')->from('users')->groupBy('unit_id')->join('units', 'units.id', '=', 'users.unit_id')->havingRaw('COUNT(users.id) < MAX(units.max_residents)'));
                                case 'full':
                                    return $query->whereIn('id', fn($q) => $q->select('unit_id')->from('users')->groupBy('unit_id')->join('units', 'units.id', '=', 'users.unit_id')->havingRaw('COUNT(users.id) = MAX(units.max_residents)'));
                                case 'over':
                                    return $query->whereIn('id', fn($q) => $q->select('unit_id')->from('users')->groupBy('unit_id')->join('units', 'units.id', '=', 'users.unit_id')->havingRaw('COUNT(users.id) > MAX(units.max_residents)'));
                            }
                        });
                    })
            ])
            ->recordActions([
                ViewAction::make(),
            ]);


    }
}
