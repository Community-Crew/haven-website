<?php


namespace App\Http\Enums;
use JsonSerializable;

enum ReservationStatus: string implements JsonSerializable
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function text_color(): string
    {
        return match ($this) {
            self::PENDING, self::APPROVED, self::REJECTED, self::CANCELLED => 'text-haven-white',
        };
    }

    public function background_color(): string
    {
        return match ($this) {
            self::PENDING => 'bg-orange-600',
            self::APPROVED => 'bg-green-600',
            self::REJECTED, self::CANCELLED => 'bg-red-600',
        };
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
            'label' => $this->label(),
            'text_color' => $this->text_color(),
            'background_color' => $this->background_color(),
        ];
    }
}
