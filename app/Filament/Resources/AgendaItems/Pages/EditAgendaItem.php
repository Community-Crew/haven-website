<?php

namespace App\Filament\Resources\AgendaItems\Pages;

use App\Filament\Resources\AgendaItems\AgendaItemResource;
use App\Filament\Resources\AgendaItems\Schemas\AgendaItemForm;
use App\Filament\Support\MediaCoverPicker;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAgendaItem extends EditRecord
{
    protected static string $resource = AgendaItemResource::class;

    protected ?array $pendingCoverImageData = null;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return [
            ...$data,
            ...MediaCoverPicker::hydrate($this->record, AgendaItemForm::COVER_STATE_KEY, AgendaItemForm::COVER_COLLECTION),
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
            AgendaItemForm::COVER_STATE_KEY,
            AgendaItemForm::COVER_COLLECTION,
            $this->pendingCoverImageData ?? [],
        );
    }
}
