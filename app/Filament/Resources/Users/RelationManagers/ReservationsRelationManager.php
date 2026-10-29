<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\Reservations\ReservationResource;
use Filament\Resources\RelationManagers\RelationManager;

/**
 * Lets an admin looking at one resident jump straight into any of their
 * reservations - and from there into that reservation's check-in/out photos
 * and notes - without a separate trip through the Reservations list and a
 * name search. Uses ReservationResource's own table/columns (including the
 * notes/check status columns added there) rather than redefining them here.
 */
class ReservationsRelationManager extends RelationManager
{
    protected static string $relationship = 'reservations';

    protected static ?string $relatedResource = ReservationResource::class;
}
