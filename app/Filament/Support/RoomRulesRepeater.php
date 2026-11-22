<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;

/**
 * One item per rule: residents tick each of them separately.
 */
class RoomRulesRepeater
{
    public static function make(): Repeater
    {
        return Repeater::make('rules')
            ->hiddenLabel()
            ->schema([
                Textarea::make('text')
                    ->label('Rule (NL - leading)')
                    ->rows(2)
                    ->required(),
                Textarea::make('text_en')
                    ->label('Rule (EN - translation)')
                    ->rows(2),
            ])
            ->columns(2)
            ->defaultItems(0)
            ->reorderable()
            ->addActionLabel('Add rule');
    }
}
