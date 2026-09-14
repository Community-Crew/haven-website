<?php

namespace App\Filament\Resources\BoardPositions\Pages;

use App\Filament\Resources\BoardPositions\BoardPositionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBoardPositions extends ListRecords
{
    protected static string $resource = BoardPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
