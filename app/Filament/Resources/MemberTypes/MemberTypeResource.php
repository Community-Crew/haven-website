<?php

namespace App\Filament\Resources\MemberTypes;

use App\Filament\Resources\MemberTypes\Pages\CreateMemberType;
use App\Filament\Resources\MemberTypes\Pages\EditMemberType;
use App\Filament\Resources\MemberTypes\Pages\ListMemberTypes;
use App\Filament\Resources\MemberTypes\Pages\ViewMemberType;
use App\Filament\Resources\MemberTypes\Schemas\MemberTypeForm;
use App\Filament\Resources\MemberTypes\Schemas\MemberTypeInfolist;
use App\Filament\Resources\MemberTypes\Tables\MemberTypesTable;
use App\Models\MemberType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MemberTypeResource extends Resource
{
    protected static ?string $model = MemberType::class;

    protected static ?string $navigationLabel = 'Member Types';

    protected static string|null|\UnitEnum $navigationGroup = 'Setup';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Tag;

    public static function form(Schema $schema): Schema
    {
        return MemberTypeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MemberTypeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MemberTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMemberTypes::route('/'),
            'create' => CreateMemberType::route('/create'),
            'view' => ViewMemberType::route('/{record}'),
            'edit' => EditMemberType::route('/{record}/edit'),
        ];
    }
}
