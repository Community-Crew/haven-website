<?php

namespace App\Filament\Resources\MemberTypes\Pages;

use App\Filament\Concerns\LinksBackToParentResource;
use App\Filament\Resources\Memberships\MembershipResource;
use App\Filament\Resources\MemberTypes\MemberTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMemberType extends EditRecord
{
    use LinksBackToParentResource;

    protected static string $resource = MemberTypeResource::class;

    protected static function getBackToResource(): string
    {
        return MembershipResource::class;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
