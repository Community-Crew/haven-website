<?php

namespace App\Filament\Resources\BoardPositionSignatures\Pages;

use App\Filament\Resources\BoardPositionSignatures\BoardPositionSignatureResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBoardPositionSignature extends EditRecord
{
    protected static string $resource = BoardPositionSignatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
