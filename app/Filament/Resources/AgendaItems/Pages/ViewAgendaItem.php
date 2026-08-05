<?php

namespace App\Filament\Resources\AgendaItems\Pages;

use App\Filament\Resources\AgendaItems\AgendaItemResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAgendaItem extends ViewRecord
{
    protected static string $resource = AgendaItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
