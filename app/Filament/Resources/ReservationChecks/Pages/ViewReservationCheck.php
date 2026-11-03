<?php

namespace App\Filament\Resources\ReservationChecks\Pages;

use App\Filament\Resources\ReservationChecks\ReservationCheckResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewReservationCheck extends ViewRecord
{
    protected static string $resource = ReservationCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('review')
                ->label('Open in Tiedr')
                ->icon('heroicon-o-fire')
                ->url(fn (): string => ReservationCheckResource::getUrl('review', ['check' => $this->record->getKey()])),
        ];
    }
}
