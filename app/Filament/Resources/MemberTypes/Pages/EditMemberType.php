<?php

namespace App\Filament\Resources\MemberTypes\Pages;

use App\Filament\Resources\MemberTypes\MemberTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMemberType extends EditRecord
{
    protected static string $resource = MemberTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
