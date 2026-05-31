<?php

namespace App\Filament\Resources\Units;

use App\Filament\Resources\Units\Pages\ListUnits;
use App\Filament\Resources\Units\Pages\ViewUnit;
use App\Filament\Resources\Units\Schemas\UnitInfolist;
use App\Filament\Resources\Units\Tables\UnitsTable;
use App\Models\Unit;
use BackedEnum;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\EnumeratesValues;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static ?string $recordTitleAttribute = 'building';


    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    public static function getNavigationGroup(): ?string
    {
        return __('filament-shield::filament-shield.nav.group');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['building', 'floor', 'unit', 'subunit'];
    }

    public static function getGlobalSearchResults(string $search): Collection
    {
        return parent::getGlobalSearchResults()->where(function (Builder $query) use ($search) {
            $query->whereRaw("
            CASE
                WHEN LOWER(building) = 'terra'
                THEN building || ' ' || LPAD(floor::text, 2, '0') || LPAD(unit::text, 2, '0') || '-' || COALESCE(subunit, '')
                ELSE building || ' ' || LPAD(floor::text, 2, '0') || LPAD(unit::text, 2, '0') || COALESCE(subunit, '')
            END ILIKE ?
        ", ["%{$search}%"]);
        });
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Building' => $record->building,
        ];
    }


    public static function infolist(Schema $schema): Schema
    {
        return UnitInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UnitsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\UsersRelationManager::class,
            RelationManagers\RegistrationCodesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUnits::route('/'),
            'view' => ViewUnit::route('/{record}'),
        ];
    }
}
