<?php

namespace App\Filament\Resources\BoardPositionAssignments;

use App\Filament\Resources\BoardPositionAssignments\Pages\CreateBoardPositionAssignment;
use App\Filament\Resources\BoardPositionAssignments\Pages\EditBoardPositionAssignment;
use App\Filament\Resources\BoardPositionAssignments\Pages\ListBoardPositionAssignments;
use App\Filament\Resources\BoardPositionAssignments\Pages\ViewBoardPositionAssignment;
use App\Filament\Resources\BoardPositionAssignments\Schemas\BoardPositionAssignmentForm;
use App\Filament\Resources\BoardPositionAssignments\Schemas\BoardPositionAssignmentInfolist;
use App\Filament\Resources\BoardPositionAssignments\Tables\BoardPositionAssignmentsTable;
use App\Models\BoardPositionAssignment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Who holds which board position - a user can hold several at once (chair
 * of one commission, treasurer of the board, ...), see
 * BoardPositionAssignment's docblock.
 */
class BoardPositionAssignmentResource extends Resource
{
    protected static ?string $model = BoardPositionAssignment::class;

    protected static ?string $navigationLabel = 'Board Positions Held';

    protected static ?string $modelLabel = 'board position assignment';

    protected static string|null|\UnitEnum $navigationGroup = 'Members';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserGroup;

    public static function form(Schema $schema): Schema
    {
        return BoardPositionAssignmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BoardPositionAssignmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BoardPositionAssignmentsTable::configure($table);
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
            'index' => ListBoardPositionAssignments::route('/'),
            'create' => CreateBoardPositionAssignment::route('/create'),
            'view' => ViewBoardPositionAssignment::route('/{record}'),
            'edit' => EditBoardPositionAssignment::route('/{record}/edit'),
        ];
    }
}
