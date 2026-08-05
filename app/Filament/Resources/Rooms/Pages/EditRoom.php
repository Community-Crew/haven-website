<?php

namespace App\Filament\Resources\Rooms\Pages;

use App\Filament\Resources\Rooms\RoomResource;
use App\Filament\Resources\Rooms\Schemas\RoomForm;
use App\Filament\Support\MediaCoverPicker;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRoom extends EditRecord
{
    protected static string $resource = RoomResource::class;

    protected ?array $pendingCoverImageData = null;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return [
            ...$data,
            ...MediaCoverPicker::hydrate($this->record, RoomForm::COVER_STATE_KEY, RoomForm::COVER_COLLECTION),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingCoverImageData = $data;

        return $data;
    }

    protected function afterSave(): void
    {
        MediaCoverPicker::sync(
            $this->record,
            RoomForm::COVER_STATE_KEY,
            RoomForm::COVER_COLLECTION,
            $this->pendingCoverImageData ?? [],
        );
    }
}
