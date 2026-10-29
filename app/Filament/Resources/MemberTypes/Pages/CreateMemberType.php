<?php

namespace App\Filament\Resources\MemberTypes\Pages;

use App\Filament\Concerns\LinksBackToParentResource;
use App\Filament\Resources\Memberships\MembershipResource;
use App\Filament\Resources\MemberTypes\MemberTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMemberType extends CreateRecord
{
    use LinksBackToParentResource;

    protected static string $resource = MemberTypeResource::class;

    protected static function getBackToResource(): string
    {
        return MembershipResource::class;
    }
}
