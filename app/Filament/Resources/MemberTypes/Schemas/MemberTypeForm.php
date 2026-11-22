<?php

namespace App\Filament\Resources\MemberTypes\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
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
                Toggle::make('is_beneficiary')
                    ->label('Beneficiary (begunstiger)')
                    ->helperText('For former residents, who apply themselves. Their data is kept for as long as this membership is open.')
                    ->live()
                    ->disabled(fn (Get $get) => (bool) $get('is_honorary'))
                    ->default(false),
                Toggle::make('is_honorary')
                    ->label('Honorary (erelid)')
                    ->helperText('Appointed by the board, never through an application. Stays after moving out, and their data is kept for as long as this membership is open.')
                    ->live()
                    ->disabled(fn (Get $get) => (bool) $get('is_beneficiary'))
                    ->default(false),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->required(),
            ]);
    }
}
