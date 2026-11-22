<?php

namespace App\Filament\Resources\Reservations;

use App\Filament\Resources\ReservationChecks\ReservationCheckResource;
use App\Filament\Resources\Reservations\Pages\CreateReservation;
use App\Filament\Resources\Reservations\Pages\EditReservation;
use App\Filament\Resources\Reservations\Pages\ListReservations;
use App\Filament\Resources\Reservations\Pages\ReservationAgenda;
use App\Filament\Resources\Reservations\Pages\ViewReservation;
use App\Filament\Resources\Reservations\RelationManagers\ChecksRelationManager;
use App\Filament\Resources\Reservations\RelationManagers\NotesRelationManager;
use App\Filament\Resources\Reservations\Schemas\ReservationForm;
use App\Filament\Resources\Reservations\Schemas\ReservationInfolist;
use App\Filament\Resources\Reservations\Tables\ReservationsTable;
use App\Models\Reservation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReservationResource extends Resource
{
    protected static ?string $model = Reservation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|null|\UnitEnum $navigationGroup = 'Bookings';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Photo review lives under Reservations, so its waiting count shows here.
     */
    public static function getNavigationBadge(): ?string
    {
        return ReservationCheckResource::canViewAny() ? ReservationCheckResource::getNavigationBadge() : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationItemActiveRoutePattern(): string|array
    {
        return [static::getRouteBaseName().'.*', ReservationCheckResource::getRouteBaseName().'.*'];
    }

    public static function form(Schema $schema): Schema
    {
        return ReservationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ReservationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReservationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ChecksRelationManager::class,
            NotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            // The agenda is the landing page; the table stays for search,
            // filters and bulk actions. 'list' must come before 'view' so
            // /list isn't captured as a {record}.
            'index' => ReservationAgenda::route('/'),
            'list' => ListReservations::route('/list'),
            'create' => CreateReservation::route('/create'),
            'view' => ViewReservation::route('/{record}'),
            'edit' => EditReservation::route('/{record}/edit'),
        ];
    }
}
