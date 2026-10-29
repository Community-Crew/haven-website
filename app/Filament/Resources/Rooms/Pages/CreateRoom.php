<?php

namespace App\Filament\Resources\Rooms\Pages;

use App\Filament\Resources\Rooms\RoomResource;
use App\Filament\Resources\Rooms\Schemas\RoomForm;
use App\Filament\Support\MediaCoverPicker;
use App\Filament\Support\MediaGalleryPicker;
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

        MediaGalleryPicker::sync(
            $this->record,
            RoomForm::CHECKIN_EXAMPLE_STATE_KEY,
            RoomForm::CHECKIN_EXAMPLE_COLLECTION,
            $this->pendingCoverImageData ?? [],
        );
    }
}
