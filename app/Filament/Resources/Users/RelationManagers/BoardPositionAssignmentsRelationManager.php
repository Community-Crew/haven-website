<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\BoardPositionAssignments\Schemas\BoardPositionAssignmentForm;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Board position form without the "User" select - the owning User record
 * fills that in via the relationship, see BoardPositionAssignmentForm::positionComponents().
 */
class BoardPositionAssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'boardPositionAssignments';

    protected static ?string $title = 'Board Positions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(BoardPositionAssignmentForm::positionComponents(userId: $this->getOwnerRecord()->getKey()));
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('boardPosition.name')
                    ->label('Board position'),
                TextColumn::make('boardPosition.organisation.name')
                    ->label('Commission')
                    ->placeholder('Global'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->state(fn ($record) => $record->ended_at === null),
                IconColumn::make('is_public')
                    ->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
