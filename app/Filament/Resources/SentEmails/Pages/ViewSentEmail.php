<?php

namespace App\Filament\Resources\SentEmails\Pages;

use App\Filament\Resources\SentEmails\SentEmailResource;
use Filament\Resources\Pages\ViewRecord;

class ViewSentEmail extends ViewRecord
{
    protected static string $resource = SentEmailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
