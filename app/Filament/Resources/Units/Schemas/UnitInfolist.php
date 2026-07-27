<?php

namespace App\Filament\Resources\Units\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UnitInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('max_residents')
                    ->numeric(),
            ]);
    }
}
