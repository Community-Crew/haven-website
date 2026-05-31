<?php

namespace App\Filament\Resources\Units\Resources\RegistrationCodes;

use App\Filament\Resources\Units\Resources\RegistrationCodes\Pages\CreateRegistrationCode;
use App\Filament\Resources\Units\Resources\RegistrationCodes\Pages\EditRegistrationCode;
use App\Filament\Resources\Units\Resources\RegistrationCodes\Pages\ViewRegistrationCode;
use App\Filament\Resources\Units\Resources\RegistrationCodes\Schemas\RegistrationCodeForm;
use App\Filament\Resources\Units\Resources\RegistrationCodes\Schemas\RegistrationCodeInfolist;
use App\Filament\Resources\Units\Resources\RegistrationCodes\Tables\RegistrationCodesTable;
use App\Filament\Resources\Units\UnitResource;
use App\Models\RegistrationCode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RegistrationCodesResource extends Resource
{
    protected static ?string $model = RegistrationCode::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $parentResource = UnitResource::class;

    protected static ?string $recordTitleAttribute = 'code';

    public static function table(Table $table): Table
    {
        return RegistrationCodesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [];
    }
}
