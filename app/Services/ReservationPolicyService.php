<?php

namespace App\Services;

use App\Models\ReservationPolicy;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReservationPolicyService
{

    public function getMergedTimeSlots(Carbon $date)
    {
        $policies = $this->getUserPolicies();

        if ($policies->isEmpty()) {
            return [];
        }

        // Calculate days difference (normalized to start of day)
        $today = Carbon::now()->startOfDay();
        $targetDate = $date->copy()->startOfDay();

        // Prevent past dates
        if ($targetDate->lt($today)) {
            return [];
        }

        $daysInFuture = $today->diffInDays($targetDate);
        $dayOfWeek = $date->dayOfWeek;

        $rawRanges = [];

        foreach ($policies as $policy) {
            // CHECK: Is the date too far in the future?
            // (Assuming 'days_in_advance' can be null for no limit)
            if (isset($policy->days_in_advance) && $daysInFuture > $policy->days_in_advance) {
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
    public function getMergedTimeSlotsOnWeekday(int $dayOfWeek)
    {
        $policies = $this->getUserPolicies();

        if ($policies->isEmpty()) {
            return [];
        }

        $rawRanges = [];

        foreach ($policies as $policy) {
            // NO CHECK for days_in_advance here.

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

    public function getAllDaysInAdvance(): array
    {
        $policies = $this->getUserPolicies();

        if ($policies->isEmpty()) {
            return [];
        }

        return $policies->pluck('max_days_in_advance')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    // ----------------------------------------------------------------
    // PRIVATE HELPERS
    // ----------------------------------------------------------------

    /**
     * Helper to fetch policies based on session roles.
     */
    private function getUserPolicies(): Collection
    {
        $roles = session('roles');

        if (empty($roles)) {
            return collect([]);
        }

        return ReservationPolicy::whereIn('role_name', $roles)->get();
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
            // If the new range starts before (or exactly when) the current range ends
            if ($range['start'] <= $current['end']) {
                // Extend the current range if the new range ends later
                if ($range['end'] > $current['end']) {
                    $current['end'] = $range['end'];
                }
            } else {
                // No overlap, push current and start a new one
                $merged[] = $current;
                $current = $range;
            }
        }

        $merged[] = $current;

        return $merged;
    }
}
