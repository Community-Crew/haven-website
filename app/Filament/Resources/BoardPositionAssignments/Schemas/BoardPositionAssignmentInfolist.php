<?php

namespace App\Filament\Resources\BoardPositionAssignments\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class BoardPositionAssignmentInfolist
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
                IconEntry::make('is_public')
                    ->label('Public')
                    ->boolean(),
                TextEntry::make('sort_order')
                    ->label('Display order'),
                TextEntry::make('ended_at')
                    ->dateTime()
                    ->placeholder('Still active'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
