<?php

namespace App\Filament\Resources\ReservationPolicies\Pages;

use App\Filament\Resources\ReservationPolicies\ReservationPolicyResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewReservationPolicy extends ViewRecord
{
    protected static string $resource = ReservationPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
