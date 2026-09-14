<?php

namespace App\Filament\Resources\BoardPositionSignatures;

use App\Filament\Resources\BoardPositionSignatures\Pages\CreateBoardPositionSignature;
use App\Filament\Resources\BoardPositionSignatures\Pages\EditBoardPositionSignature;
use App\Filament\Resources\BoardPositionSignatures\Pages\ListBoardPositionSignatures;
use App\Filament\Resources\BoardPositionSignatures\Pages\ViewBoardPositionSignature;
use App\Filament\Resources\BoardPositionSignatures\Schemas\BoardPositionSignatureForm;
use App\Filament\Resources\BoardPositionSignatures\Schemas\BoardPositionSignatureInfolist;
use App\Filament\Resources\BoardPositionSignatures\Tables\BoardPositionSignaturesTable;
use App\Models\BoardPositionSignature;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BoardPositionSignatureResource extends Resource
{
    protected static ?string $model = BoardPositionSignature::class;

    protected static ?string $navigationLabel = 'NDA Signatures';

    protected static ?string $modelLabel = 'NDA signature';

    protected static string|null|\UnitEnum $navigationGroup = 'Members';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::PencilSquare;

    public static function form(Schema $schema): Schema
    {
        return BoardPositionSignatureForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BoardPositionSignatureInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BoardPositionSignaturesTable::configure($table);
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
            'index' => ListBoardPositionSignatures::route('/'),
            'create' => CreateBoardPositionSignature::route('/create'),
            'view' => ViewBoardPositionSignature::route('/{record}'),
            'edit' => EditBoardPositionSignature::route('/{record}/edit'),
        ];
    }
}
