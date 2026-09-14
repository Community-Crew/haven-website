<?php

namespace App\Filament\Resources\BoardPositionAssignments\Pages;

use App\Filament\Resources\BoardPositionAssignments\BoardPositionAssignmentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBoardPositionAssignment extends ViewRecord
{
    protected static string $resource = BoardPositionAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
