<?php

namespace App\Filament\Resources\Media\Actions;

use App\Models\Media;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

/**
 * A lightweight rename-only action (not a full edit form) - Media names are
 * mainly there so the "choose existing" library picker has something more
 * searchable than an original filename like IMG_2481.jpg to show/filter by.
 */
class RenameMediaAction
{
    public static function make(): Action
    {
        return Action::make('rename')
            ->label('Rename')
            ->icon(Heroicon::Pencil)
            ->schema([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
            ])
            ->fillForm(fn (Media $record): array => ['name' => $record->name])
            ->action(function (Media $record, array $data): void {
                $record->update(['name' => $data['name']]);
            });
    }
}
