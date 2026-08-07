<?php

namespace App\Filament\Resources\AgendaItems\Pages;

use App\Filament\Resources\AgendaItems\AgendaItemResource;
use App\Filament\Resources\AgendaItems\Schemas\AgendaItemForm;
use App\Filament\Support\MediaCoverPicker;
use Filament\Resources\Pages\CreateRecord;

class CreateAgendaItem extends CreateRecord
{
    protected static string $resource = AgendaItemResource::class;

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
            AgendaItemForm::COVER_STATE_KEY,
            AgendaItemForm::COVER_COLLECTION,
            $this->pendingCoverImageData ?? [],
        );
    }
}
