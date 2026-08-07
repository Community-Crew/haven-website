<?php

namespace App\Filament\Resources\Media\Schemas;

use App\Models\Media;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Illuminate\Support\Number;

class MediaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                ImageEntry::make('url')
                    ->label('Preview')
                    ->columnSpanFull(),
                TextEntry::make('api_url')
                    ->label('API URL')
                    ->state(fn (Media $record): string => route('api.v1.media.show', ['media' => $record]))
                    ->copyable()
                    ->copyMessage('Copied!'),
                TextEntry::make('name')
                    ->placeholder('-'),
                TextEntry::make('collection')
                    ->badge()
                    ->placeholder('-'),
                TextEntry::make('mime_type')
                    ->label('Type')
                    ->placeholder('-'),
                TextEntry::make('size')
                    ->formatStateUsing(fn (?int $state): string => $state === null ? '-' : Number::fileSize($state)),
                TextEntry::make('mediable_type')
                    ->label('Used By')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-'),
                TextEntry::make('mediable_id')
                    ->label('Used By ID')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
