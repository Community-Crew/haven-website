<?php

namespace App\Filament\Resources\ReservationPolicies;

use App\Filament\Resources\ReservationPolicies\Pages\CreateReservationPolicy;
use App\Filament\Resources\ReservationPolicies\Pages\EditReservationPolicy;
use App\Filament\Resources\ReservationPolicies\Pages\ListReservationPolicies;
use App\Filament\Resources\ReservationPolicies\Pages\ViewReservationPolicy;
use App\Filament\Resources\ReservationPolicies\RelationManagers\EntriesRelationManager;
use App\Filament\Resources\ReservationPolicies\RelationManagers\RoomsRelationManager;
use App\Filament\Resources\ReservationPolicies\Schemas\ReservationPolicyForm;
use App\Filament\Resources\ReservationPolicies\Schemas\ReservationPolicyInfolist;
use App\Filament\Resources\ReservationPolicies\Tables\ReservationPoliciesTable;
use App\Models\ReservationPolicy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReservationPolicyResource extends Resource
{
    protected static ?string $model = ReservationPolicy::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Setup';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Scale;

    public static function form(Schema $schema): Schema
    {
        return ReservationPolicyForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ReservationPolicyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReservationPoliciesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReservationPolicies::route('/'),
            'create' => CreateReservationPolicy::route('/create'),
            'view' => ViewReservationPolicy::route('/{record}'),
            'edit' => EditReservationPolicy::route('/{record}/edit'),
        ];
    }
}
