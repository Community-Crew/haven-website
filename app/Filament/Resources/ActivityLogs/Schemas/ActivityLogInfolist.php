<?php

namespace App\Filament\Resources\ActivityLogs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Spatie\Activitylog\Models\Activity;

class ActivityLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('description'),
                TextEntry::make('event')
                    ->badge()
                    ->placeholder('-'),
                TextEntry::make('log_name')
                    ->label('Log')
                    ->badge()
                    ->color('gray'),
                TextEntry::make('causer.name')
                    ->label('By')
                    ->placeholder('System'),
                TextEntry::make('subject_type')
                    ->label('Subject')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-'),
                TextEntry::make('subject_id')
                    ->label('Subject ID')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->label('When')
                    ->dateTime(),
                TextEntry::make('attribute_changes')
                    ->label('Changes')
                    ->formatStateUsing(fn (Activity $record): string => '<pre class="fi-in-text text-xs whitespace-pre-wrap">'
                        .e(json_encode($record->attribute_changes, JSON_PRETTY_PRINT))
                        .'</pre>')
                    ->html()
                    ->visible(fn (Activity $record): bool => filled($record->attribute_changes))
                    ->columnSpanFull(),
                TextEntry::make('properties')
                    ->formatStateUsing(fn (Activity $record): string => '<pre class="fi-in-text text-xs whitespace-pre-wrap">'
                        .e(json_encode($record->properties, JSON_PRETTY_PRINT))
                        .'</pre>')
                    ->html()
                    ->visible(fn (Activity $record): bool => filled($record->properties))
                    ->columnSpanFull(),
            ]);
    }
}
