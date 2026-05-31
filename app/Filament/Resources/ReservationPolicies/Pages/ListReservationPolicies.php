<?php

namespace App\Filament\Resources\ReservationPolicies\Pages;

use App\Filament\Resources\ReservationPolicies\ReservationPolicyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReservationPolicies extends ListRecords
{
    protected static string $resource = ReservationPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
