<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Unit;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('keycloak_id')
                    ->required(),
                Select::make('unit_id')
                    ->relationship(name: 'unit', titleAttribute: 'id')
                    ->getOptionLabelFromRecordUsing(fn (Unit $record) => $record->name)
                    ->searchable()
                    ->preload(),
            ]);
    }
}
