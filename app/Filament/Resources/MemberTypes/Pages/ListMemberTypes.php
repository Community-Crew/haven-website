<?php

namespace App\Filament\Resources\MemberTypes\Pages;

use App\Filament\Concerns\LinksBackToParentResource;
use App\Filament\Resources\Memberships\MembershipResource;
use App\Filament\Resources\MemberTypes\MemberTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMemberTypes extends ListRecords
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
            $this->getBackToParentAction(),
            CreateAction::make(),
        ];
    }
}
