<?php

namespace App\Filament\Resources\Commissions\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CommissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('slug'),
                IconEntry::make('is_commission')
                    ->label('Internal commission')
                    ->boolean(),
                TextEntry::make('about')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('nda_text')
                    ->label('Default NDA text')
                    ->placeholder('-')
                    ->columnSpanFull(),
                // image_url is HasS3Image's accessor - already a signed
                // temporary URL, not a disk-relative path, so no ->disk()
                // here (that would treat it as a storage key to resolve).
                ImageEntry::make('image_url')
                    ->label('Cover image')
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
