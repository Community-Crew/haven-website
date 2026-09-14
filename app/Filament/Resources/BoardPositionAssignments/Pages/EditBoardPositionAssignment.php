<?php

namespace App\Filament\Resources\BoardPositionAssignments\Pages;

use App\Filament\Resources\BoardPositionAssignments\BoardPositionAssignmentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBoardPositionAssignment extends EditRecord
{
    protected static string $resource = BoardPositionAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
