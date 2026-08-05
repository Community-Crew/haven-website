<?php

namespace App\Filament\Resources\Rooms\Schemas;

use App\Enums\RoomStatus;
use App\Filament\Support\MediaCoverPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoomForm
{
    public const COVER_STATE_KEY = 'cover_image';

    public const COVER_COLLECTION = 'rooms-cover';

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
                            ->mapWithKeys(fn ($status) => [$status->getValue() => $status->getLabel()])
                            ->all();
                    })
                    ->default(RoomStatus::AVAILABLE->getValue())
                    ->inline()
                    ->colors(
                        collect(RoomStatus::cases())
                            ->mapWithKeys(fn ($status) => [$status->value => $status->getColor()])
                            ->all()
                    )
                    ->required(),
                Section::make('Room Media Management')
                    ->description('Manage the public cover image and internal exit instructions.')
                    ->schema(MediaCoverPicker::formComponents(
                        self::COVER_STATE_KEY,
                        self::COVER_COLLECTION,
                        'Main Room Preview (Single)',
                        required: true,
                    )),
            ]);
    }
}
