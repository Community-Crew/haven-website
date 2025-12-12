<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ReservationPolicy;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;

class TimeSlotService
{
    public function getMergedTimeSlots(Carbon $date)
    {
        $roles = session('roles');

        if(empty($roles)){
            return [];
        }

        $policies = ReservationPolicy::whereIn('role_name', $roles)->get();

        if(empty($policies)){
            return [];
        }

        $rawRanges = [];
        $daysInAdvance = $date->diffInDays(Carbon::now()->startOfDay());

        foreach ($policies as $policy) {
            if ($daysInAdvance > $policy->max_days_in_advance) {
                continue;
            }

            $dayOfWeek = $date->dayOfWeek;
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
}
