<?php

namespace App\Filament\Resources\MemberTypes\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MemberTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->columnSpanFull(),
                Toggle::make('has_voting_rights')
                    ->label('Voting rights by default')
                    ->helperText('Used as the starting value when a membership of this type is created; can still be overridden per membership.')
                    ->default(true),
                Toggle::make('is_active')
                    ->helperText('Inactive types stay on existing memberships but can no longer be picked for new ones.')
                    ->default(true),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->required(),
            ]);
    }
}
