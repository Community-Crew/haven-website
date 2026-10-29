<?php

namespace App\Filament\Resources\Memberships\Pages;

use App\Filament\Resources\Memberships\MembershipResource;
use App\Filament\Resources\MemberTypes\MemberTypeResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListMemberships extends ListRecords
{
    protected static string $resource = MembershipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('memberTypes')
                ->label('Member Types')
                ->icon(Heroicon::Tag)
                ->color('gray')
                ->url(fn () => MemberTypeResource::getUrl('index'))
                ->visible(fn () => MemberTypeResource::canViewAny()),
            CreateAction::make(),
        ];
    }
}
