<?php

namespace App\Filament\Resources\BoardPositionSignatures\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class BoardPositionSignatureInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')
                    ->label('User'),
                TextEntry::make('boardPosition.name')
                    ->label('Board position'),
                TextEntry::make('boardPosition.organisation.name')
                    ->label('Commission')
                    ->placeholder('Global'),
                TextEntry::make('signed_at')
                    ->dateTime()
                    ->placeholder('Not signed yet'),
                TextEntry::make('external_reference')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
