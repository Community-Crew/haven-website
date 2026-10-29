<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use JsonSerializable;

enum CheckVerificationStatus: string implements JsonSerializable
{
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::VERIFIED => 'Verified',
            self::REJECTED => 'Rejected',
        };
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getColor(): array
    {
        return match ($this) {
            self::PENDING => Color::Amber,
            self::VERIFIED => Color::Green,
            self::REJECTED => Color::Red,
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
