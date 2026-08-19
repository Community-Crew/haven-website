<?php

namespace App\Filament\Resources\SentEmails\Pages;

use App\Filament\Resources\SentEmails\SentEmailResource;
use Filament\Resources\Pages\ListRecords;

class ListSentEmails extends ListRecords
{
    protected static string $resource = SentEmailResource::class;
}
