<?php

namespace App\Services;

use App\Models\ReservationPolicy;

class ReservationPolicyService
{
    public function getMergedTimeSlots(int $dayOfWeek)
    {
        $roles = session('roles');

        if (empty($roles)) {
            return [];
        }

        $policies = ReservationPolicy::whereIn('role_name', $roles)->get();

        if (empty($policies)) {
            return [];
        }

        $rawRanges = [];

        foreach ($policies as $policy) {
            $schedule = $policy->weekly_schedule[$dayOfWeek] ?? null;

            if ($schedule) {
                $rawRanges[] = [
                    'start' => $schedule['start'],
                    'end' => $schedule['end']
                ];
            }
        }

        if (empty($rawRanges)) {
            return [];
        }

        usort($rawRanges, function ($a, $b) {
            return strcmp($a['start'], $b['start']);
        });

        $merged = [];
        $current = $rawRanges[0];

        foreach ($rawRanges as $range) {
            if ($range['start'] <= $current['end']) {

                if ($range['end'] > $current['end']) {
                    $current['end'] = $range['end'];
                }
            } else {
                $merged[] = $current;
                $current = $range;
            }
        }

        $merged[] = $current;

        return $merged;
    }

    public function getDaysInAdvance()
    {
        $roles = session('roles');

        if (empty($roles)) {
            return [];
        }

        return ReservationPolicy::whereIn('role_name', $roles)->pluck('max_days_in_advance')
            ->toArray();

    }
}
