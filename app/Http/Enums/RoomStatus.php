<?php

namespace App\Http\Enums;

use JsonSerializable;

enum RoomStatus: string implements JsonSerializable
{
    case AVAILABLE = 'available';
    case OCCUPIED = 'occupied';
    case RESERVED = 'reserved';
    case MAINTENANCE = 'maintenance';
    case CLEANING = 'cleaning';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Available',
            self::OCCUPIED => 'Occupied',
            self::RESERVED => 'Reserved',
            self::MAINTENANCE => 'Out of Order',
            self::CLEANING => 'Deep Cleaning',
        };
    }

    public function text_color(): string
    {
        return match ($this) {
            self::AVAILABLE, self::OCCUPIED, self::RESERVED, self::MAINTENANCE, self::CLEANING => 'text-haven-white',
        };
    }

    public function background_color(): string
    {
        return match ($this) {
            self::AVAILABLE => 'bg-green-600',
            self::OCCUPIED => 'bg-blue-600',
            self::RESERVED => 'bg-cyan-600',
            self::MAINTENANCE => 'bg-red-600',
            self::CLEANING => 'bg-orange-600',
        };
    }


    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->name,
            'label' => $this->label(),
            'text_color' => $this->text_color(),
            'background_color' => $this->background_color(),
        ];
    }
}
