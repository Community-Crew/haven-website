<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationService
{
    public function __construct(
        protected ReservationPolicyService $policyService
    ) {}

    public function createReservation(array $data)
    {
        return DB::transaction(function () use ($data) {
            $start = Carbon::parse($data['start_time']);
            $end = Carbon::parse($data['end_time']);
            $roomId = $data['room_id'];

            Room::where('id', $roomId)->lockForUpdate()->first();

            $this->verifyPolicyAllows($start, $end);

            $this->ensureNoOverlap($roomId, $start, $end);

            return Reservation::create([
                'name' => $data['name'],
                'share_name' => $data['share_name'],
                'room_id' => $roomId,
                'user_id' => auth()->id(),
                'organisation_id' => $data['organisation'] ?? null,
                'start_at' => $start,
                'end_at' => $end,
                'status' => ReservationStatus::APPROVED,
            ]);
        });
    }

    public function updateReservation(Reservation $reservation, array $data): Reservation
    {
        return DB::transaction(function () use ($reservation, $data) {
            $start = Carbon::parse($data['start_time']);
            $end = Carbon::parse($data['end_time']);
            $roomId = $data['room_id'];

            Room::where('id', $roomId)->lockForUpdate()->first();

            $this->verifyPolicyAllows($start, $end);

            $this->ensureNoOverlap($roomId, $start, $end, $reservation->id);

            $reservation->update([
                'name' => $data['name'],
                'share_name' => $data['share_name'],
                'room_id' => $roomId,
                'organisation_id' => $data['organisation'] ?? null,
                'start_at' => $start,
                'end_at' => $end,
            ]);

            return $reservation;
        });
    }

    protected function verifyPolicyAllows(Carbon $start, Carbon $end): void
    {
        $policies = $this->policyService->getUserPolicies($start);

        $startMin = ($start->hour * 60) + $start->minute;
        $endMin = ($end->hour * 60) + $end->minute;
        if ($end->format('H:i') === '00:00' && $end->isNextDay($start)) {
            $endMin = 1440;
        }
        $isAllowed = $policies->contains(function ($policy) use ($startMin, $endMin) {
            $pStart = (int) $policy->getAttributes()['start_time'];
            $pEnd = (int) $policy->getAttributes()['end_time'];

            return $startMin >= $pStart && $endMin <= $pEnd;
        });

        if (! $isAllowed) {
            throw ValidationException::withMessages([
                'start_time' => 'The selected time range is not permitted by your user policy.',
            ]);
        }
    }

    protected function ensureNoOverlap(int $roomId, Carbon $start, Carbon $end, ?int $excludeId = null): void
    {
        $query = Reservation::where('room_id', $roomId)
            ->whereIn('status', [ReservationStatus::APPROVED, ReservationStatus::PENDING])
            ->where(function ($q) use ($start, $end) {
                $q->where('start_at', '<', $end)
                    ->where('end_at', '>', $start);
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'start_time' => 'This room is already booked for the selected time.',
            ]);
        }
    }
}
