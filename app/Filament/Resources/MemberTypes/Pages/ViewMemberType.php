<?php

namespace App\Filament\Resources\MemberTypes\Pages;

use App\Filament\Concerns\LinksBackToParentResource;
use App\Filament\Resources\Memberships\MembershipResource;
use App\Filament\Resources\MemberTypes\MemberTypeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMemberType extends ViewRecord
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
            EditAction::make(),
        ];
    }
}
