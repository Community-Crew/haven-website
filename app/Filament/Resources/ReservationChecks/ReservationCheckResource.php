<?php

namespace App\Filament\Resources\ReservationChecks;

use App\Enums\CheckVerificationStatus;
use App\Filament\Resources\ReservationChecks\Pages\ListReservationChecks;
use App\Filament\Resources\ReservationChecks\Pages\ReviewReservationChecks;
use App\Filament\Resources\ReservationChecks\Pages\ViewReservationCheck;
use App\Filament\Resources\ReservationChecks\Schemas\ReservationCheckInfolist;
use App\Filament\Resources\ReservationChecks\Tables\ReservationChecksTable;
use App\Models\ReservationCheck;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Rows only ever originate from the resident-facing API (check-in/check-out
 * uploads) - no create/edit UI, only the admin review queue (list) and a
 * detail view with Approve/Reject.
 */
class ReservationCheckResource extends Resource
{
    protected static ?string $model = ReservationCheck::class;

    protected static ?string $navigationLabel = 'Check-in/out Photos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCamera;

    protected static string|null|\UnitEnum $navigationGroup = 'Bookings';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $pending = ReservationCheck::query()->where('verification_status', CheckVerificationStatus::PENDING)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return ReservationCheckInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReservationChecksTable::configure($table);
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
            'index' => ListReservationChecks::route('/'),
            // Must come before 'view' so /review isn't captured as a {record}.
            'review' => ReviewReservationChecks::route('/review'),
            'view' => ViewReservationCheck::route('/{record}'),
        ];
    }
}
