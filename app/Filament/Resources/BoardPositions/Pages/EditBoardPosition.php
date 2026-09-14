<?php

namespace App\Filament\Resources\BoardPositions\Pages;

use App\Filament\Resources\BoardPositions\BoardPositionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBoardPosition extends EditRecord
{
    protected static string $resource = BoardPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
