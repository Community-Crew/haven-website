<?php

namespace App\Filament\Resources\AgendaItems;

use App\Filament\Resources\AgendaItems\Pages\CreateAgendaItem;
use App\Filament\Resources\AgendaItems\Pages\EditAgendaItem;
use App\Filament\Resources\AgendaItems\Pages\ListAgendaItems;
use App\Filament\Resources\AgendaItems\Pages\ViewAgendaItem;
use App\Filament\Resources\AgendaItems\Schemas\AgendaItemForm;
use App\Filament\Resources\AgendaItems\Schemas\AgendaItemInfolist;
use App\Filament\Resources\AgendaItems\Tables\AgendaItemsTable;
use App\Models\AgendaItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AgendaItemResource extends Resource
{
    protected static ?string $model = AgendaItem::class;

    protected static ?string $navigationLabel = 'Agenda Items';

    protected static string|null|\UnitEnum $navigationGroup = 'Agenda';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Ticket;

    public static function form(Schema $schema): Schema
    {
        return AgendaItemForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AgendaItemInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AgendaItemsTable::configure($table);
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
            'index' => ListAgendaItems::route('/'),
            'create' => CreateAgendaItem::route('/create'),
            'view' => ViewAgendaItem::route('/{record}'),
            'edit' => EditAgendaItem::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
