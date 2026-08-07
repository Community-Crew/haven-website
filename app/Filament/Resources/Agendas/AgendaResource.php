<?php

namespace App\Filament\Resources\Agendas;

use App\Filament\Resources\Agendas\Pages\CreateAgenda;
use App\Filament\Resources\Agendas\Pages\EditAgenda;
use App\Filament\Resources\Agendas\Pages\ListAgendas;
use App\Filament\Resources\Agendas\Pages\ViewAgenda;
use App\Filament\Resources\Agendas\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\Agendas\Schemas\AgendaForm;
use App\Filament\Resources\Agendas\Schemas\AgendaInfolist;
use App\Filament\Resources\Agendas\Tables\AgendasTable;
use App\Models\Agenda;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AgendaResource extends Resource
{
    protected static ?string $model = Agenda::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Agenda';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Calendar;

    public static function form(Schema $schema): Schema
    {
        return AgendaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AgendaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AgendasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAgendas::route('/'),
            'create' => CreateAgenda::route('/create'),
            'view' => ViewAgenda::route('/{record}'),
            'edit' => EditAgenda::route('/{record}/edit'),
        ];
    }
}
