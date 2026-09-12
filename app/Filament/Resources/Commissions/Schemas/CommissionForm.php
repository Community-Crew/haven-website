<?php

namespace App\Filament\Resources\Commissions\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CommissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->helperText('The slug used for the public commission page is generated from this automatically.'),
                Toggle::make('is_commission')
                    ->label('This is an internal commission')
                    ->helperText('Off for an external organisation stored here for other purposes (the landlord Vestide, etc.) - those don\'t get default board positions/roles.')
                    ->default(true),
                Textarea::make('about')
                    ->columnSpanFull(),
                Textarea::make('nda_text')
                    ->label('Default NDA text')
                    ->helperText('Used by any of this commission\'s requires_nda positions that don\'t set their own NDA text.')
                    ->rows(6)
                    ->columnSpanFull(),
                FileUpload::make('image_path')
                    ->label('Cover image')
                    ->disk('hetzner')
                    ->directory('organisations')
                    ->visibility('private')
                    ->image(),
            ]);
    }
}
