<?php

namespace App\Services;

use App\Models\ReservationPolicy;
use App\Models\ReservationPolicyEntry;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class ReservationPolicyService
{
    public function getUserPolicies(Carbon $date, Room $room): Collection
    {
        $days = (int) $date->copy()->diffInDays(Carbon::now()->startOfDay(), true);
        $dayOfWeek = $date->dayOfWeek;

        $roles = collect(Session::get('roles', []));

        $policyNames = $roles->filter(fn ($role) => Str::startsWith($role, 'reservation_policy-'))
            ->map(fn ($role) => Str::after($role, 'reservation_policy-'));

        $reservationPolicies = ReservationPolicy::whereIn('role_name', $policyNames)
            ->where('max_days_in_advance', '>=', $days)
            ->whereHas('rooms', function ($query) use ($room) {
                $query->where('rooms.id', $room->id);
            })
            ->get(['id', 'max_days_in_advance']);

        if ($reservationPolicies->isEmpty()) {
            return collect();
        }

        $entries = ReservationPolicyEntry::whereIn('reservation_policy_id', $reservationPolicies->pluck('id'))
            ->where(function ($query) use ($dayOfWeek) {
                $query->where('day_of_week', $dayOfWeek)
                    ->orWhere('day_of_week', 8);
            })
            ->orderBy('start_time')
            ->get()
            ->map(function ($entry) use ($reservationPolicies) {
                $policy = $reservationPolicies->firstWhere('id', $entry->reservation_policy_id);
                $entry->max_days_in_advance = $policy ? $policy->max_days_in_advance : 0;

                return $entry;
            });

        return $this->mergeEntries($entries);
    }

    protected function mergeEntries(Collection $entries): Collection
    {
        if ($entries->isEmpty()) {
            return collect();
        }

        $merged = collect();
        $current = clone $entries->first();

        foreach ($entries->skip(1) as $next) {
            if ($next->start_time <= $current->end_time) {
                $current->end_time = max($current->end_time, $next->end_time);
                $current->max_days_in_advance = max($current->max_days_in_advance, $next->max_days_in_advance);
            } else {
                $merged->push($current);
                $current = clone $next;
            }
        }

        $merged->push($current);

        return $merged;
    }

    public function getWeeklySchedule(Room $room, int $daysOut = 7): array
    {
        $schedule = [];

        $startDate = Carbon::now()->startOfWeek();

        for ($i = 0; $i < $daysOut; $i++) {
            $date = $startDate->copy()->addDays($i);

            $mergedEntries = $this->getUserPolicies($date, $room);

            $schedule[] = [
                'date' => $date->toDateString(),
                'day_name' => $date->format('l'),
                'is_today' => $date->isToday(),
                'is_past' => $date->isPast() && ! $date->isToday(),
                'entries' => $mergedEntries->map(fn ($entry) => [
                    'start' => $entry->start_time,
                    'end' => $entry->end_time,
                    'max_days' => $entry->max_days_in_advance,
                    'label' => ($entry->start_time.' - '.$entry->end_time),
                ]),
            ];
        }

        return $schedule;
    }
}
