<?php

namespace App\Filament\Resources\ReservationPolicies\RelationManagers;

use App\Filament\Resources\Rooms\RoomResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class RoomsRelationManager extends RelationManager
{
    protected static string $relationship = 'rooms';

    protected static ?string $relatedResource = RoomResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
