<?php

namespace App\Filament\Resources\BoardPositions\Pages;

use App\Filament\Concerns\LinksBackToParentResource;
use App\Filament\Resources\BoardPositionAssignments\BoardPositionAssignmentResource;
use App\Filament\Resources\BoardPositions\BoardPositionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBoardPositions extends ListRecords
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
            $this->getBackToParentAction(),
            CreateAction::make(),
        ];
    }
}
