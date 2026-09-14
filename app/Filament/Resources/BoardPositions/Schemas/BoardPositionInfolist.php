<?php

namespace App\Filament\Resources\BoardPositions\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class BoardPositionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('organisation.name')
                    ->label('Commission')
                    ->placeholder('Global'),
                TextEntry::make('shieldRole.name')
                    ->label('Role')
                    ->placeholder('-'),
                TextEntry::make('sort_order'),
                IconEntry::make('requires_nda')
                    ->label('Requires NDA')
                    ->boolean(),
                // Shows the effective text (this position's own override, or
                // its commission's default) via BoardPosition::ndaText(),
                // not the raw nda_text column - visible(requires_nda) since
                // an unset one is never actually used.
                TextEntry::make('effective_nda_text')
                    ->label('Effective NDA text')
                    ->state(fn ($record) => $record->ndaText())
                    ->placeholder('None set')
                    ->visible(fn ($record) => $record->requires_nda)
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
