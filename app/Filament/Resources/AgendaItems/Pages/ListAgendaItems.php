<?php

namespace App\Filament\Resources\AgendaItems\Pages;

use App\Filament\Resources\AgendaItems\AgendaItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAgendaItems extends ListRecords
{
    protected static string $resource = AgendaItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
