<?php

namespace App\Filament\Resources\SentEmails\Tables;

use App\Models\SentEmail;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SentEmailsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sent_at')
                    ->label('When')
                    ->dateTime()
                    ->sortable()
                    ->since(),
                TextColumn::make('mailable')
                    ->label('Mail')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => class_basename($state)),
                TextColumn::make('to')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('subject')
                    ->searchable(),
                TextColumn::make('locale')
                    ->badge()
                    ->color('gray'),
            ])
            ->defaultSort('sent_at', 'desc')
            ->filters([
                SelectFilter::make('mailable')
                    ->options(fn () => SentEmail::query()
                        ->distinct()
                        ->pluck('mailable')
                        ->mapWithKeys(fn (string $fqcn): array => [$fqcn => class_basename($fqcn)])
                        ->all()),
                SelectFilter::make('locale')
                    ->options(fn () => SentEmail::query()
                        ->whereNotNull('locale')
                        ->distinct()
                        ->pluck('locale', 'locale')
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
