<?php

namespace App\Filament\Resources\AgendaItems\Schemas;

use App\Models\AgendaItem;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AgendaItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title'),
                TextEntry::make('agenda.name')
                    ->label('Agenda')
                    ->badge(),
                TextEntry::make('short_description'),
                TextEntry::make('description')
                    ->html()
                    ->columnSpanFull(),
                TextEntry::make('start_date')
                    ->dateTime(),
                TextEntry::make('end_date')
                    ->dateTime(),
                ImageEntry::make('image_url')
                    ->label('Cover Image')
                    ->placeholder('-'),
                TextEntry::make('user.name')
                    ->label('Organiser'),
                TextEntry::make('organisation.name')
                    ->label('Organisation'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (AgendaItem $record): bool => $record->trashed()),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
