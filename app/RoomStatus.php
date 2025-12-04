<?php

namespace App;

enum RoomStatus: string
{
    case AVAILABLE = 'available';
    case OCCUPIED = 'occupied';
    case RESERVED = 'reserved';
    case MAINTENANCE = 'maintenance';
    case CLEANING = 'cleaning';

    public function label(): string
    {
        return match($this) {
            self::AVAILABLE => 'Available',
            self::OCCUPIED => 'Occupied',
            self::RESERVED => 'Reserved',
            self::MAINTENANCE => 'Out of Order',
            self::CLEANING => 'Housekeeping',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::AVAILABLE => 'text-green-600',
            self::OCCUPIED => 'text-blue-600',
            self::RESERVED => 'text-cyan-600',
            self::MAINTENANCE => 'text-red-600',
            self::CLEANING => 'text-orange-600',
        };
    }
}
