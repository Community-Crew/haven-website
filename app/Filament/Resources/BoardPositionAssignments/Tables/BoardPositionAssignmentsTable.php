<?php

namespace App\Filament\Resources\BoardPositionAssignments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class BoardPositionAssignmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable(),
                TextColumn::make('boardPosition.name')
                    ->label('Board position')
                    ->searchable(),
                TextColumn::make('boardPosition.organisation.name')
                    ->label('Commission')
                    ->placeholder('Global'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->state(fn ($record) => $record->ended_at === null),
                IconColumn::make('is_public')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('ended_at')
                    ->label('Active')
                    ->nullable()
                    ->trueLabel('Active')
                    ->falseLabel('Ended')
                    ->queries(
                        true: fn ($query) => $query->whereNull('ended_at'),
                        false: fn ($query) => $query->whereNotNull('ended_at'),
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
