<?php

namespace App\Filament\Resources\Rooms\Pages;

use App\Filament\Resources\Rooms\RoomResource;
use App\Filament\Resources\Rooms\Schemas\RoomForm;
use App\Filament\Support\MediaCoverPicker;
use Filament\Resources\Pages\CreateRecord;

class CreateRoom extends CreateRecord
{
    protected static string $resource = RoomResource::class;

    protected ?array $pendingCoverImageData = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingCoverImageData = $data;

        return $data;
    }

    protected function afterCreate(): void
    {
        MediaCoverPicker::sync(
            $this->record,
            RoomForm::COVER_STATE_KEY,
            RoomForm::COVER_COLLECTION,
            $this->pendingCoverImageData ?? [],
        );
    }
}
