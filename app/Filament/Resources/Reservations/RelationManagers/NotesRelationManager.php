<?php

namespace App\Filament\Resources\Reservations\RelationManagers;

use App\Models\ReservationNote;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

/**
 * A dated/authored log, not a scratchpad - a note's body is never edited
 * once posted (no EditAction), only deleted outright if it was a mistake.
 * visible_to_resident is the one thing meant to be flipped after the fact,
 * so it gets a live ToggleColumn instead of living behind an edit form.
 */
class NotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('body')
                    ->label('Note')
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('visible_to_resident')
                    ->label('Visible to resident')
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->columns([
                TextColumn::make('author.name')
                    ->label('By')
                    ->placeholder('-'),
                TextColumn::make('body')
                    ->wrap()
                    ->columnSpanFull(),
                // Editable columns bypass policies on their own (Filament
                // only skips hidden/disabled ones), so gate it explicitly -
                // otherwise view-only admins could publish internal notes.
                ToggleColumn::make('visible_to_resident')
                    ->label('Visible to resident')
                    ->disabled(fn (ReservationNote $record): bool => ! auth()->user()->can('update', $record)),
                TextColumn::make('created_at')
                    ->dateTime('d/m/y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();

                        return $data;
                    }),
            ])
            ->recordActions([
                DeleteAction::make(),
            ]);
    }
}
