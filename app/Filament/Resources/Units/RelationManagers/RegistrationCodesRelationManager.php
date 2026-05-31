<?php

namespace App\Filament\Resources\Units\RelationManagers;

use App\Filament\Resources\Units\Resources\RegistrationCodes\RegistrationCodesResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class RegistrationCodesRelationManager extends RelationManager
{
    protected static string $relationship = 'registrationCodes';

    protected static ?string $relatedResource = RegistrationCodesResource::class;

    public function table(Table $table): Table
    {
        return $table;
    }
}
