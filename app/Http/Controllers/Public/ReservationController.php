<?php

namespace App\Http\Controllers\Public;

use App\Http\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\ReservationPolicyService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    private string $timezone = 'Europe/Amsterdam';

    /**
     * @throws ValidationException
     */
    public function store(Request $request)
    {
        // 1. Validate inputs using shared rules
        $validated = $request->validate($this->getValidationRules($request));

        // 2. Prepare data
        $reqStart = $this->parseDateTime($validated['start_time']);
        $reqEnd = $this->parseDateTime($validated['end_time']);
        $room = Room::findOrFail($validated['room'] ?? $validated['room_id']);

        // 3. Logic Checks (Policy & Overlap)
        $this->ensureWithinPolicy($room, $reqStart, $reqEnd);
        $this->ensureNoOverlap($room->id, $reqStart, $reqEnd);

        // 4. Create
        Reservation::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'room_id' => $room->id,
            'status' => 'approved',
            'start_at' => $reqStart,
            'end_at' => $reqEnd,
            'share_user' => $validated['share_name'],
            'organisation' => $validated['organisation'],
        ]);

        return redirect()->back()->with('success', 'Reservation created successfully.');
    }

    /**
     * @throws ValidationException
     */
    public function update(Request $request, Reservation $reservation)
    {
        // 1. Authorization
        if ($reservation->user_id !== $request->user()->id) {
            abort(403, 'You can only edit your own reservations.');
        }
        if ($reservation->start_at < Carbon::now()) {
            abort(403, 'You can only edit future reservations.');
        }

        if (! ($reservation->status == ReservationStatus::APPROVED || $reservation->status == ReservationStatus::PENDING)) {
            abort(403, 'You can only edit approved or pending reservations.');
        }

        // 2. Validate inputs using shared rules
        $validated = $request->validate($this->getValidationRules($request));

        // 3. Prepare data
        $reqStart = $this->parseDateTime($validated['start_time']);
        $reqEnd = $this->parseDateTime($validated['end_time']);
        $room = Room::findOrFail($validated['room_id']);

        // 4. Logic Checks (Policy & Overlap - excluding current ID)
        $this->ensureWithinPolicy($room, $reqStart, $reqEnd);
        $this->ensureNoOverlap($room->id, $reqStart, $reqEnd, $reservation->id);

        // 5. Update
        $reservation->update([
            'name' => $validated['name'],
            'room_id' => $room->id,
            'start_at' => $reqStart,
            'end_at' => $reqEnd,
            'share_user' => $validated['share_name'],
            'organisation_id' => $validated['organisation'],
        ]);

        return redirect()->back()->with('success', 'Reservation updated successfully.');
    }

    public function destroy(Request $request, Reservation $reservation)
    {
        if ($reservation->user_id !== $request->user()->id) {
            abort(403, 'You can only cancel your own reservations.');
        }
        if ($reservation->start_at < Carbon::now()) {
            abort(403, 'You can only cancel future reservations.');
        }

        $reservation->update([
            'status' => ReservationStatus::CANCELLED,
        ]);

        return redirect()->back()->with('success', 'Reservation cancelled.');
    }

    private function getValidationRules(Request $request): array
    {
        $user = $request->user();

        return [
            'name' => 'required|string|max:255',
            'share_name' => 'required|boolean',

            'room_id' => 'required|exists:rooms,id',

            'start_time' => [
                'required', 'string',
                function ($attribute, $value, $fail) {
                    $date = $this->parseDateTime($value);
                    if (! $date) {
                        return $fail('Invalid date.');
                    }
                    if ($date->isPast()) {
                        return $fail('Start time must be in the future.');
                    }
                    if ($date->minute !== 0 && $date->minute !== 30) {
                        return $fail('Start time must be on the hour or half-hour.');
                    }

                    return true;
                },
            ],

            'end_time' => [
                'required', 'string',
                function ($attribute, $value, $fail) use ($request) {
                    $start = $this->parseDateTime($request->input('start_time'));
                    $end = $this->parseDateTime($value);

                    if (! $end) {
                        return $fail('Invalid date.');
                    }

                    if ($start && $end->lte($start)) {
                        return $fail('End time must be after start.');
                    }

                    if ($end->minute !== 0 && $end->minute !== 30) {
                        return $fail('End time must be on the hour or half-hour.');
                    }

                    if ($start) {
                        $isSameDay = $start->isSameDay($end);
                        $isMidnightNextDay = $end->format('H:i') === '00:00' && $end->isSameDay($start->copy()->addDay());

                        if (! $isSameDay && ! $isMidnightNextDay) {
                            $fail('The start and end time must be on the same day.');
                        }
                    }

                    return true;
                },
            ],
            'organisation' => [
                'nullable',
                'exists:organisations,id',
                function ($attribute, $value, $fail) use ($user) {
                    if ($value !== null && ! $user->reservations->contains($value)) {
                        return $fail('You can only use organisations you are a member of.');
                    }
                },
            ],
        ];
    }

    /**
     * Parses ISO dates or the custom "T24:00" format.
     */
    private function parseDateTime(?string $timeString): ?Carbon
    {
        if (! $timeString) {
            return null;
        }

        if (str_ends_with($timeString, 'T24:00')) {
            try {
                return Carbon::createFromFormat('Y-m-d', substr($timeString, 0, 10), $this->timezone)
                    ->addDay()
                    ->startOfDay();
            } catch (\Exception $e) {
                return null;
            }
        }

        // Handle standard ISO
        try {
            return Carbon::createFromFormat('Y-m-d\TH:i', $timeString, $this->timezone);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function ensureWithinPolicy(Room $room, Carbon $reqStart, Carbon $reqEnd)
    {
        $service = new ReservationPolicyService;
        $allowedSlots = $service->getMergedTimeSlots($reqStart, $room);
        $dateString = $reqStart->format('Y-m-d');
        $isWithinPolicy = false;

        foreach ($allowedSlots as $slot) {
            $policyStart = Carbon::createFromFormat('Y-m-d H:i', $dateString.' '.$slot['start'], $this->timezone);
            $policyEnd = Carbon::createFromFormat('Y-m-d H:i', $dateString.' '.$slot['end'], $this->timezone);

            if ($slot['end'] === '24:00') {
                $policyEnd->addDay()->startOfDay();
            }

            if ($reqStart->gte($policyStart) && $reqEnd->lte($policyEnd)) {
                $isWithinPolicy = true;
                break;
            }
        }

        if (! $isWithinPolicy) {
            throw ValidationException::withMessages([
                'start_time' => 'The selected time is outside your allowed reservation hours.',
            ]);
        }
    }

    private function ensureNoOverlap(int $roomId, Carbon $start, Carbon $end, ?int $excludeId = null)
    {
        $query = Reservation::where('room_id', $roomId)
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
