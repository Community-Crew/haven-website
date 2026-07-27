<?php

namespace App\Filament\Resources\ReservationPolicies\Pages;

use App\Filament\Resources\ReservationPolicies\ReservationPolicyResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditReservationPolicy extends EditRecord
{
    protected static string $resource = ReservationPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
