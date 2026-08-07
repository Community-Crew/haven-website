<?php

namespace App\Filament\Resources\Agendas\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AgendaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Textarea::make('description')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
                Toggle::make('public')
                    ->label('Public')
                    ->helperText('Publicly visible agendas are shown to all visitors. Private agendas are hidden from the public site.')
                    ->default(true)
                    ->required(),
            ]);
    }
}
