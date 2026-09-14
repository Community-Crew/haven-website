<?php

namespace App\Filament\Resources\BoardPositions;

use App\Filament\Resources\BoardPositions\Pages\CreateBoardPosition;
use App\Filament\Resources\BoardPositions\Pages\EditBoardPosition;
use App\Filament\Resources\BoardPositions\Pages\ListBoardPositions;
use App\Filament\Resources\BoardPositions\Pages\ViewBoardPosition;
use App\Filament\Resources\BoardPositions\Schemas\BoardPositionForm;
use App\Filament\Resources\BoardPositions\Schemas\BoardPositionInfolist;
use App\Filament\Resources\BoardPositions\Tables\BoardPositionsTable;
use App\Models\BoardPosition;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BoardPositionResource extends Resource
{
    protected static ?string $model = BoardPosition::class;

    protected static ?string $navigationLabel = 'Board Positions';

    protected static string|null|\UnitEnum $navigationGroup = 'Members';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Identification;

    public static function form(Schema $schema): Schema
    {
        return BoardPositionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BoardPositionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BoardPositionsTable::configure($table);
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
            'index' => ListBoardPositions::route('/'),
            'create' => CreateBoardPosition::route('/create'),
            'view' => ViewBoardPosition::route('/{record}'),
            'edit' => EditBoardPosition::route('/{record}/edit'),
        ];
    }
}
