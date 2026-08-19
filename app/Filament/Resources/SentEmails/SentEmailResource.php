<?php

namespace App\Filament\Resources\SentEmails;

use App\Filament\Resources\SentEmails\Pages\ListSentEmails;
use App\Filament\Resources\SentEmails\Pages\ViewSentEmail;
use App\Filament\Resources\SentEmails\Schemas\SentEmailInfolist;
use App\Filament\Resources\SentEmails\Tables\SentEmailsTable;
use App\Models\SentEmail;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Read-only view onto sent_emails (populated by App\Listeners\LogSentEmail
 * for every Mailable the app sends). No create/edit/delete - this is a
 * record of what actually went out, not something to be authored here.
 */
class SentEmailResource extends Resource
{
    protected static ?string $model = SentEmail::class;

    protected static ?string $navigationLabel = 'Sent Emails';

    protected static string|null|\UnitEnum $navigationGroup = 'Communication';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Envelope;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return SentEmailInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SentEmailsTable::configure($table);
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
            'index' => ListSentEmails::route('/'),
            'view' => ViewSentEmail::route('/{record}'),
        ];
    }
}
