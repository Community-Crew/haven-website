<?php

namespace App\Filament\Resources\BoardPositions\Pages;

use App\Filament\Resources\BoardPositions\BoardPositionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBoardPosition extends CreateRecord
{
    protected static string $resource = BoardPositionResource::class;
}
