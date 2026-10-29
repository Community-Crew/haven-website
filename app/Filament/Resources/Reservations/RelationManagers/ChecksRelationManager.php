<?php

namespace App\Filament\Resources\Reservations\RelationManagers;

use App\Filament\Resources\ReservationChecks\ReservationCheckResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class ChecksRelationManager extends RelationManager
{
    protected static string $relationship = 'checks';

    protected static ?string $relatedResource = ReservationCheckResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('type');
    }
}
