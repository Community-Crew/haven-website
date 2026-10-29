<?php

namespace App\Enums;

use JsonSerializable;

enum CheckType: string implements JsonSerializable
{
    case CHECK_IN = 'check_in';
    case CHECK_OUT = 'check_out';

    public function getLabel(): string
    {
        return match ($this) {
            self::CHECK_IN => 'Check-in',
            self::CHECK_OUT => 'Check-out',
        };
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
            'label' => $this->getLabel(),
        ];
    }
}
