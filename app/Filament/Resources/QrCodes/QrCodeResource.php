<?php

namespace App\Filament\Resources\QrCodes;

use App\Filament\Resources\QrCodes\Pages\CreateQrCode;
use App\Filament\Resources\QrCodes\Pages\EditQrCode;
use App\Filament\Resources\QrCodes\Pages\ListQrCodes;
use App\Filament\Resources\QrCodes\Pages\ViewQrCode;
use App\Filament\Resources\QrCodes\Schemas\QrCodeForm;
use App\Filament\Resources\QrCodes\Schemas\QrCodeInfolist;
use App\Filament\Resources\QrCodes\Tables\QrCodesTable;
use App\Models\QrCode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class QrCodeResource extends Resource
{
    protected static ?string $model = QrCode::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static string|null|\UnitEnum $navigationGroup = 'System';

    public static function form(Schema $schema): Schema
    {
        return QrCodeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return QrCodeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QrCodesTable::configure($table);
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
            'index' => ListQrCodes::route('/'),
            'create' => CreateQrCode::route('/create'),
            'view' => ViewQrCode::route('/{record}'),
            'edit' => EditQrCode::route('/{record}/edit'),
        ];
    }
}
