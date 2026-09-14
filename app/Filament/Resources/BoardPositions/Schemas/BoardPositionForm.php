<?php

namespace App\Filament\Resources\BoardPositions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BoardPositionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Select::make('organisation_id')
                    ->label('Commission')
                    ->relationship('organisation', 'name')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->helperText('Leave blank for a global board position (Chair, Secretary, Treasurer). Set it to scope this position to one commission.'),
                // Only exposed for global positions - a commission-scoped
                // position gets its role auto-provisioned instead of
                // hand-picked here (BoardPositionObserver, Part E).
                Select::make('shield_role_id')
                    ->label('Role')
                    ->relationship('shieldRole', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn ($get) => blank($get('organisation_id')))
                    ->helperText('Which permission role holding this position grants.'),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Toggle::make('requires_nda')
                    ->label('Requires NDA')
                    ->helperText('If enabled, a user assigned this position only gets its role/permissions once an NDA is recorded as signed for them.')
                    ->live(),
                Textarea::make('nda_text')
                    ->label('NDA text override')
                    ->helperText('Leave blank to use the commission\'s default NDA text instead.')
                    ->rows(6)
                    ->visible(fn ($get) => (bool) $get('requires_nda'))
                    ->columnSpanFull(),
            ]);
    }
}
