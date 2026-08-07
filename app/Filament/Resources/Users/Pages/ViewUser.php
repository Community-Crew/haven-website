<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Concerns\LogsRecordView;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    use LogsRecordView;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
