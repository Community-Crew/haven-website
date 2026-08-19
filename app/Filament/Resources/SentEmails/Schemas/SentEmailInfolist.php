<?php

namespace App\Filament\Resources\SentEmails\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SentEmailInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('subject'),
                TextEntry::make('mailable')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => class_basename($state)),
                TextEntry::make('to'),
                TextEntry::make('user.name')
                    ->label('User')
                    ->placeholder('-'),
                TextEntry::make('locale')
                    ->badge()
                    ->color('gray'),
                TextEntry::make('sent_at')
                    ->label('When')
                    ->dateTime(),
            ]);
    }
}
