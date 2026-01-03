<?php

namespace App\Services;

use App\Models\ReservationPolicy;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ReservationPolicyService
{

    public function getMergedTimeSlots(Carbon $date, Room $room): array
    {
        $policies = $this->getUserPolicies($room);

        if ($policies->isEmpty()) {
            return [];
        }

        $today = Carbon::now()->startOfDay();
        $targetDate = $date->copy()->startOfDay();

        if ($targetDate->lt($today)) {
            return [];
        }

        $daysInFuture = $today->diffInDays($targetDate);
        $dayOfWeek = $date->dayOfWeek;

        $rawRanges = [];
        foreach ($policies as $policy) {
            if (isset($policy->max_days_in_advance) && (int) $daysInFuture > (int) $policy->max_days_in_advance) {
                continue;
            }

            $schedule = $policy->weekly_schedule[$dayOfWeek] ?? null;

            if ($schedule) {
                $rawRanges[] = [
                    'start' => $schedule['start'],
                    'end'   => $schedule['end']
                ];
            }
        }

        return $this->mergeRanges($rawRanges);
    }

    /**
     * Get slots for a generic day of the week (0=Sun, 6=Sat).
     * Ignores 'days_in_advance' (used for showing general policy).
     */
    public function getMergedTimeSlotsOnWeekday(int $dayOfWeek, Room $room)
    {
        $policies = $this->getUserPolicies($room);

        if ($policies->isEmpty()) {
            return [];
        }

        $rawRanges = [];

        foreach ($policies as $policy) {
            $schedule = $policy->weekly_schedule[$dayOfWeek] ?? null;

            if ($schedule) {
                $rawRanges[] = [
                    'start' => $schedule['start'],
                    'end'   => $schedule['end']
                ];
            }
        }

        return $this->mergeRanges($rawRanges);
    }

    public function getAllDaysInAdvance(Room $room): array
    {
        $policies = $this->getUserPolicies($room);

        if ($policies->isEmpty()) {
            return [];
        }

        return $policies->pluck('max_days_in_advance')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Helper to fetch policies based on session roles.
     */
    private function getUserPolicies(Room $room): Collection
    {
        $roles = session('roles');

        if (empty($roles)) {
            return collect([]);
        }

        return ReservationPolicy::whereIn('role_name', $roles)->where('room_id', $room['id'])->get();
    }

    /**
     * Helper to sort and merge overlapping time ranges.
     */
    private function mergeRanges(array $rawRanges): array
    {
        if (empty($rawRanges)) {
            return [];
        }

        // Sort by start time
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
