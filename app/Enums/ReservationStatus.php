<?php

namespace App\Enums;

use JsonSerializable;
use Filament\Support\Colors\Color;

enum ReservationStatus: string implements JsonSerializable
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function text_color(): string
    {
        return match ($this) {
            self::PENDING, self::APPROVED, self::REJECTED, self::CANCELLED => 'text-haven-white',
        };
    }

    public function getColor(): array
    {
        return match ($this) {
            self::PENDING => Color::Amber,
            self::APPROVED => Color::Green,
            self::REJECTED => Color::Red,
            self::CANCELLED => Color::Slate,
        };
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
            'label' => $this->getLabel(),
            'text_color' => $this->text_color(),
            'background_color' => $this->getColor(),
        ];
    }
}
