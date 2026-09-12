<?php

namespace App\Filament\Resources\BoardPositionSignatures\Pages;

use App\Filament\Resources\BoardPositionSignatures\BoardPositionSignatureResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBoardPositionSignature extends ViewRecord
{
    protected static string $resource = BoardPositionSignatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
