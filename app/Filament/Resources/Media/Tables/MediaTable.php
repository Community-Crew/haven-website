<?php

namespace App\Filament\Resources\Media\Tables;

use App\Filament\Resources\Media\Actions\RenameMediaAction;
use App\Models\Media;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Number;

class MediaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('url')
                    ->label('Preview')
                    ->square(),
                TextColumn::make('name')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('collection')
                    ->badge()
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('mime_type')
                    ->label('Type')
                    ->placeholder('-'),
                TextColumn::make('size')
                    ->formatStateUsing(fn (?int $state): string => $state === null ? '-' : Number::fileSize($state))
                    ->sortable(),
                TextColumn::make('mediable_type')
                    ->label('Used By')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('collection')
                    ->options(fn () => Media::query()
                        ->whereNotNull('collection')
                        ->distinct()
                        ->pluck('collection', 'collection')
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make(),
                RenameMediaAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
