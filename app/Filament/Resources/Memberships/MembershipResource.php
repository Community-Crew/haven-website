<?php

namespace App\Filament\Resources\Memberships;

use App\Filament\Resources\Memberships\Pages\CreateMembership;
use App\Filament\Resources\Memberships\Pages\EditMembership;
use App\Filament\Resources\Memberships\Pages\ListMemberships;
use App\Filament\Resources\Memberships\Pages\ViewMembership;
use App\Filament\Resources\Memberships\Schemas\MembershipForm;
use App\Filament\Resources\Memberships\Schemas\MembershipInfolist;
use App\Filament\Resources\Memberships\Tables\MembershipsTable;
use App\Models\Membership;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MembershipResource extends Resource
{
    protected static ?string $model = Membership::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Membership';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return MembershipForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MembershipInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MembershipsTable::configure($table);
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
            'index' => ListMemberships::route('/'),
            'create' => CreateMembership::route('/create'),
            'view' => ViewMembership::route('/{record}'),
            'edit' => EditMembership::route('/{record}/edit'),
        ];
    }
}
