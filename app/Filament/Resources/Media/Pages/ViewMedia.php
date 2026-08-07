<?php

namespace App\Filament\Resources\Media\Pages;

use App\Filament\Resources\Media\Actions\RenameMediaAction;
use App\Filament\Resources\Media\MediaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMedia extends ViewRecord
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RenameMediaAction::make(),
            DeleteAction::make(),
        ];
    }
}
