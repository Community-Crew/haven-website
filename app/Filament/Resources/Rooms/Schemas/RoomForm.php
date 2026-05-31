<?php

namespace App\Filament\Resources\Rooms\Schemas;

use App\Enums\RoomStatus;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('description')
                    ->required(),
                TextInput::make('location')
                    ->required(),
                ToggleButtons::make('status')
                    ->options(function () {
                        return collect(RoomStatus::cases())
                            ->mapWithKeys(fn($status) => [$status->getValue() => $status->getLabel()])
                            ->all();
                    })
                    ->default(RoomStatus::AVAILABLE->getValue())
                    ->inline()
                    ->colors(
                        collect(RoomStatus::cases())
                            ->mapWithKeys(fn($status) => [$status->value => $status->getColor()])
                            ->all()
                    )
                    ->required(),
                Section::make('Room Media Management')
                    ->description('Manage the public cover image and internal exit instructions.')
                    ->schema([
                        FileUpload::make('image_path')
                            ->label('Main Room Preview (Single)')
                            ->disk('hetzner')
                            ->directory('rooms/covers')
                            ->image()
                            ->imageAspectRatio('3:2')
                            ->automaticallyCropImagesToAspectRatio()
                            ->automaticallyResizeImagesToWidth('1200')
                            ->required(),
                    ])
            ]);
    }
}
