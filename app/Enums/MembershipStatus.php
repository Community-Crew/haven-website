<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use JsonSerializable;

enum MembershipStatus: string implements JsonSerializable
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case ENDED = 'ended';

    public function getValue(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ACTIVE => 'Active',
            self::SUSPENDED => 'Suspended',
            self::ENDED => 'Ended',
        };
    }

    public function getColor(): array
    {
        return match ($this) {
            self::PENDING => Color::Amber,
            self::ACTIVE => Color::Green,
            self::SUSPENDED => Color::Orange,
            self::ENDED => Color::Gray,
        };
    }

    /**
     * Statuses that count as "currently holding a membership" - used to
     * enforce that a user has at most one of these at a time.
     *
     * @return array<self>
     */
    public static function open(): array
    {
        return [self::PENDING, self::ACTIVE, self::SUSPENDED];
    }

    public function isOpen(): bool
    {
        return in_array($this, self::open(), true);
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
