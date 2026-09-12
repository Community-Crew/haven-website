<?php

namespace App\Filament\Resources\BoardPositions\Pages;

use App\Filament\Resources\BoardPositions\BoardPositionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBoardPosition extends ViewRecord
{
    protected static string $resource = BoardPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
