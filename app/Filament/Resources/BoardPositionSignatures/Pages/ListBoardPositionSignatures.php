<?php

namespace App\Filament\Resources\BoardPositionSignatures\Pages;

use App\Filament\Resources\BoardPositionSignatures\BoardPositionSignatureResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBoardPositionSignatures extends ListRecords
{
    protected static string $resource = BoardPositionSignatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
