<?php

namespace App\Http\Controllers\Public;

use App\Http\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\ReservationPolicyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Sentry\Severity;
use Sentry\State\Scope;

use function Sentry\captureMessage;
use function Sentry\configureScope;

class ReservationController extends Controller
{
    private string $timezone = 'Europe/Amsterdam';

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->getValidationRules($request));
        $room = Room::findOrFail($validated['room_id']);

        $this->ensureWithinPolicy($room, $this->parseDateTime($validated['start_time']), $this->parseDateTime($validated['end_time']));
        $this->ensureNoOverlap($room->id, $this->parseDateTime($validated['start_time']), $this->parseDateTime($validated['end_time']));

        $reservation = new Reservation;
        $this->saveReservation($reservation, $validated, $request->user()->id);

        return redirect()->route('rooms.show', $room->slug)->with('success', 'Created!');
    }

    private function saveReservation(Reservation $reservation, array $data, int $userId): void
    {
        $reservation->fill([
            'name' => $data['name'],
            'user_id' => $userId,
            'room_id' => $data['room_id'],
            'start_at' => $this->parseDateTime($data['start_time']),
            'end_at' => $this->parseDateTime($data['end_time']),
            'share_user' => $data['share_name'],
            'organisation_id' => $data['organisation'],
            'status' => $reservation->exists ? $reservation->status : ReservationStatus::APPROVED,
        ]);
        $reservation->save();
    }

    /**
     * @throws ValidationException
     */
    public function update(Request $request, Reservation $reservation): RedirectResponse
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

        $validated = $request->validate($this->getValidationRules($request));

        $reqStart = $this->parseDateTime($validated['start_time']);
        $reqEnd = $this->parseDateTime($validated['end_time']);
        $room = Room::findOrFail($validated['room_id']);

        $this->ensureWithinPolicy($room, $reqStart, $reqEnd);
        $this->ensureNoOverlap($room->id, $reqStart, $reqEnd, $reservation->id);

        $reservation->update([
            'name' => $validated['name'],
            'room_id' => $validated['room_id'],
            'start_at' => $this->parseDateTime($validated['start_time']),
            'end_at' => $this->parseDateTime($validated['end_time']),
            'share_user' => $validated['share_name'],
            'organisation_id' => $validated['organisation'],
        ]);

        return redirect()->route('profile')->with('success', 'Reservation updated successfully.');
    }

    public function destroy(Request $request, Reservation $reservation): RedirectResponse
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
                    if ($value !== null && ! $user->organisations->contains($value)) {
                        return $fail('You can only use organisations you are a member of.');
                    }

                    return true;
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

    /**
     * @throws ValidationException
     */
    private function ensureWithinPolicy(Room $room, Carbon $reqStart, Carbon $reqEnd)
    {
        $service = new ReservationPolicyService;
        $allowedSlots = $service->getMergedTimeSlots($reqStart, $room);
        $isWithinPolicy = false;

        $reqStartLocal = $reqStart->copy()->setTimezone($this->timezone);
        $reqEndLocal = $reqEnd->copy()->setTimezone($this->timezone);
        $dateString = $reqStartLocal->toDateString();

        foreach ($allowedSlots as $slot) {
            $policyStart = Carbon::parse("$dateString {$slot['start']}", $this->timezone);

            if ($slot['end'] === '24:00') {
                $policyEnd = Carbon::parse($dateString, $this->timezone)->addDay()->startOfDay();
            } else {
                $policyEnd = Carbon::parse("$dateString {$slot['end']}", $this->timezone);
            }

            if ($reqStartLocal->gte($policyStart) && $reqEndLocal->lte($policyEnd)) {
                $isWithinPolicy = true;
                break;
            }
        }

        if (! $isWithinPolicy) {
            $supportId = app()->bound('support_id') ? app('support_id') : 'OOP-'.strtoupper(Str::random(6));
            $daysInFuture = now()->diffInDays($reqStart, false);

            $isShortTerm = $daysInFuture <= 14;
            $severity = $isShortTerm ? Severity::error() : Severity::info();
            $typeLabel = $isShortTerm ? 'Policy Error (Short-term)' : 'Policy Audit (Long-term)';

            configureScope(function (Scope $scope) use ($supportId, $daysInFuture, $room, $allowedSlots, $reqStartLocal, $reqEndLocal, $severity) {
                $scope->setTag('support_id', $supportId);
                $scope->setTag('room', $room->name);
                $scope->setTag('policy_window', $daysInFuture <= 14 ? 'within_14_days' : 'beyond_14_days');
                $scope->setLevel($severity);

                $scope->setContext('reservation_details', [
                    'days_forward' => $daysInFuture,
                    'requested_range' => "{$reqStartLocal->toDateTimeString()} to {$reqEndLocal->toDateTimeString()}",
                    'applied_policy_slots' => $allowedSlots,
                    'user_id' => auth()->id(),
                ]);
            });

            captureMessage("Out-of-Policy: $typeLabel");

            throw ValidationException::withMessages([
                'start_time' => "The selected time is outside your allowed hours. (Ref: $supportId)",
            ]);
        }
    }

    private function ensureNoOverlap(int $roomId, Carbon $start, Carbon $end, ?int $excludeId = null)
    {
        $query = Reservation::where('room_id', $roomId)
            ->where('status', ReservationStatus::APPROVED)
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
