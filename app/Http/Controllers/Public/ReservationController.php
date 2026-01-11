<?php

namespace App\Http\Controllers\Public;

use App\Models\Reservation;
use App\Models\ReservationPolicy;
use App\Models\Room;
use App\Services\ReservationPolicyService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Sentry\Severity;
use Sentry\State\Scope;
use function Sentry\captureMessage;
use function Sentry\withScope;

class ReservationController extends Controller
{
    /**
     * @throws ValidationException
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'room' => 'required|exists:rooms,id',
            'start_time' => [
                'required',
                'date',
                'after:now',
                function ($attribute, $value, $fail) {
                    $carbonDate = Carbon::parse($value);
                    if ($carbonDate->minute !== 0 && $carbonDate->minute !== 30) {
                        $fail('The start time must be on the hour or half-hour (e.g. 14:00 or 14:30).');
                    }
                },
            ],
            'end_time' => [
                'required',
                'date',
                'after:start_time',
                function ($attribute, $value, $fail) use ($request) {
                    $end = Carbon::parse($value);
                    $start = $request->input('start_time') ? Carbon::parse($request->input('start_time')) : null;
                    if ($end->minute !== 0 && $end->minute !== 30) {
                        $fail('The end time must be on the hour or half-hour.');
                    }
                    if ($start && !$start->isSameDay($end)) {
                        $fail('The start and end time must be on the same day.');
                    }
                },
            ],
            'share_name' => 'required|boolean',
        ]);

        $appTz = 'Europe/Amsterdam';

        $service = new ReservationPolicyService();

        $reqStart = Carbon::createFromFormat('Y-m-d\TH:i', $validated['start_time'], $appTz);
        $reqEnd = Carbon::createFromFormat('Y-m-d\TH:i', $validated['end_time'], $appTz);


        $room = Room::findOrFail($validated['room']);
        $allowedSlots = $service->getMergedTimeSlots($reqStart, $room);
        $dateString = $reqStart->format('Y-m-d');
        $isWithinPolicy = false;

        foreach ($allowedSlots as $slot) {
            $policyStart = Carbon::createFromFormat(
                'Y-m-d H:i',
                $dateString . ' ' . $slot['start'],
                $appTz
            );

            $policyEnd = Carbon::createFromFormat(
                'Y-m-d H:i',
                $dateString . ' ' . $slot['end'],
                $appTz
            );

            if ($slot['end'] === '24:00') {
                $policyEnd->addDay()->startOfDay();
            }

            if ($reqStart->gte($policyStart) && $reqEnd->lte($policyEnd)) {
                $isWithinPolicy = true;
                break;
            }
        }

        if (!$isWithinPolicy) {
            withScope(function (Scope $scope) use ($request, $reqStart, $reqEnd, $allowedSlots, $room) {

                $scope->setUser([
                    'id' => $request->user()->id,
                    'email' => $request->user()->email,
                ]);

                $scope->setContext('policy_debug', [
                    'room_name' => $room->name,
                    'requested_start_tz' => $reqStart->format('Y-m-d H:i:s P'), // Includes Timezone offset
                    'requested_end_tz'   => $reqEnd->format('Y-m-d H:i:s P'),
                    'allowed_slots_raw'  => $allowedSlots, // Shows exactly what the Service returned
                    'roles' => session('roles'),
                    'policy' => ReservationPolicy::whereIn('role_name', session('roles'))->where('room_id', $room['id'])->get()
                ]);

                $scope->setLevel(Severity::warning());

                captureMessage('Reservation Rejected: Outside Policy');
            });
            throw ValidationException::withMessages([
                'start_time' => 'The selected time is outside your allowed reservation hours.',
            ]);
        }

        $overlapping = Reservation::where('room_id', $validated['room'])
            ->where(function ($query) use ($reqStart, $reqEnd) {
                $query->where('start_at', '<', $reqEnd)
                    ->where('end_at', '>', $reqStart);
            })
            ->exists();

        if ($overlapping) {
            throw ValidationException::withMessages([
                'start_time' => 'This room is already booked for the selected time.',
            ]);
        }

        Reservation::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'room_id' => $validated['room'],
            'status' => 'approved',
            'start_at' => $reqStart,
            'end_at' => $reqEnd,
            'share_user' => $validated['share_name'],
        ]);
    }
}
