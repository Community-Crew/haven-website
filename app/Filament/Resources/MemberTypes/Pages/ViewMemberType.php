<?php

namespace App\Filament\Resources\MemberTypes\Pages;

use App\Filament\Resources\MemberTypes\MemberTypeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMemberType extends ViewRecord
{
    protected static string $resource = MemberTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
