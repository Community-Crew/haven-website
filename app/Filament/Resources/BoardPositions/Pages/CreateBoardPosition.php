<?php

namespace App\Filament\Resources\BoardPositions\Pages;

use App\Filament\Concerns\LinksBackToParentResource;
use App\Filament\Resources\BoardPositionAssignments\BoardPositionAssignmentResource;
use App\Filament\Resources\BoardPositions\BoardPositionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBoardPosition extends CreateRecord
{
    use LinksBackToParentResource;

    protected static string $resource = BoardPositionResource::class;

    protected static function getBackToResource(): string
    {
        return BoardPositionAssignmentResource::class;
    }
}
