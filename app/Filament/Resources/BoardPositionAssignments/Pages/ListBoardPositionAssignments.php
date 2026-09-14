<?php

namespace App\Filament\Resources\BoardPositionAssignments\Pages;

use App\Filament\Resources\BoardPositionAssignments\BoardPositionAssignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBoardPositionAssignments extends ListRecords
{
    protected static string $resource = BoardPositionAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
