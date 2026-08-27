<?php

namespace App\Filament\Resources\QrCodes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class QrCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->helperText('For your own reference - not shown to whoever scans the code.')
                    ->required(),
                TextInput::make('destination_url')
                    ->label('Destination URL')
                    ->helperText('Where scanning the code currently sends people. Change this any time without reprinting the code.')
                    ->url()
                    ->required(),
                // Deliberately no field for `code` - it's generated once and
                // printed onto a physical label/poster, so it must never
                // change under an already-printed QR image.
            ]);
    }
}
