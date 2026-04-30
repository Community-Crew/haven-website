<?php

namespace App\Services;

class TimeConverterService
{
    public static function toMinutes(string $time): int
    {
        if ($time === '24:00') return 1440;

        [$hours, $minutes] = explode(':', $time);
        return ((int)$hours * 60) + (int)$minutes;
    }

    public static function fromMinutes(int $minutes): string
    {
        if ($minutes === 1440) return '24:00';

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        return sprintf('%02d:%02d', $hours, $mins);
    }
}
