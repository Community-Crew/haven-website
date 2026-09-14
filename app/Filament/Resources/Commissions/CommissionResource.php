<?php

namespace App\Filament\Resources\Commissions;

use App\Filament\Resources\Commissions\Pages\CreateCommission;
use App\Filament\Resources\Commissions\Pages\EditCommission;
use App\Filament\Resources\Commissions\Pages\ListCommissions;
use App\Filament\Resources\Commissions\Pages\ViewCommission;
use App\Filament\Resources\Commissions\Schemas\CommissionForm;
use App\Filament\Resources\Commissions\Schemas\CommissionInfolist;
use App\Filament\Resources\Commissions\Tables\CommissionsTable;
use App\Models\Organisation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Manages App\Models\Organisation rows, despite the "Commission" naming
 * here - that table also holds external organisations (the landlord
 * Vestide, etc. - see is_commission), which AgendaItem/Reservation/User
 * already reference more broadly than just commissions. Policy/permissions
 * are still named after the model (OrganisationPolicy, *:Organisation) per
 * Filament Shield's model-based convention, not this resource's name.
 */
class CommissionResource extends Resource
{
    protected static ?string $model = Organisation::class;

    protected static ?string $navigationLabel = 'Commissions';

    protected static ?string $modelLabel = 'commission';

    protected static string|null|\UnitEnum $navigationGroup = 'Members';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserGroup;

    public static function form(Schema $schema): Schema
    {
        return CommissionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CommissionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommissionsTable::configure($table);
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
            'index' => ListCommissions::route('/'),
            'create' => CreateCommission::route('/create'),
            'view' => ViewCommission::route('/{record}'),
            'edit' => EditCommission::route('/{record}/edit'),
        ];
    }
}
