<?php

namespace App\Enums;

use JsonSerializable;
use Filament\Support\Colors\Color;

enum RoomStatus: string implements JsonSerializable
{
    case AVAILABLE = 'available';
    case OCCUPIED = 'occupied';
    case RESERVED = 'reserved';
    case MAINTENANCE = 'maintenance';
    case CLEANING = 'cleaning';

    public function getValue(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Available',
            self::OCCUPIED => 'Occupied',
            self::RESERVED => 'Reserved',
            self::MAINTENANCE => 'Out of Order',
            self::CLEANING => 'Deep Cleaning',
        };
    }

    public function getColor(): array
    {
        return match ($this) {
            self::AVAILABLE => Color::Green,
            self::OCCUPIED => Color::Blue,
            self::RESERVED => Color::Cyan,
            self::MAINTENANCE => Color::Red,
            self::CLEANING => Color::Orange,
        };
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
            'label' => $this->getLabel(),
            'background_color' => $this->getColor(),
        ];
    }
}
