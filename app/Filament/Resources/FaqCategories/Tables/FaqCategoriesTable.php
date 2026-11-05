<?php

namespace App\Filament\Resources\FaqCategories\Tables;

use App\Models\FaqCategory;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FaqCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('icon')
                    ->label(''),
                TextColumn::make('title')
                    ->searchable()
                    ->description(fn (FaqCategory $record) => $record->title_en),
                IconColumn::make('has_flowchart')
                    ->label('Flowchart')
                    ->state(fn (FaqCategory $record) => filled($record->flowchart))
                    ->boolean(),
                TextColumn::make('questions_count')
                    ->label('Questions')
                    ->counts('questions'),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
