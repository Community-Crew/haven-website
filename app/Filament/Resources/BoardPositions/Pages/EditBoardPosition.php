<?php

namespace App\Filament\Resources\BoardPositions\Pages;

use App\Filament\Concerns\LinksBackToParentResource;
use App\Filament\Resources\BoardPositionAssignments\BoardPositionAssignmentResource;
use App\Filament\Resources\BoardPositions\BoardPositionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBoardPosition extends EditRecord
{
    use LinksBackToParentResource;

    protected static string $resource = BoardPositionResource::class;

    protected static function getBackToResource(): string
    {
        return BoardPositionAssignmentResource::class;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
