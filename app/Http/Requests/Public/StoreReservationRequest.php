<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reservation = $this->route('reservation');
        if ($reservation) {
            return $reservation->user_id === auth()->id();
        }

        return Auth::check();
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'name' => 'required|string|max:255',
            'share_name' => 'required|boolean',
            'room_id' => 'required|exists:rooms,id',

            'start_time' => [
                'required', 'date', 'after:now',
                function ($attribute, $value, $fail) {
                    $date = Carbon::parse($value);
                    if (! in_array($date->minute, [0, 30])) {
                        $fail('Start time must be on the hour or half-hour.');
                    }
                },
            ],

            'end_time' => [
                'required', 'date', 'after:start_time',
                function ($attribute, $value, $fail) {
                    $start = Carbon::parse($this->input('start_time'));
                    $end = Carbon::parse($value);

                    if (! $start->isSameDay($end) && ! ($end->isNextDay($start) && $end->format('H:i') === '00:00')) {
                        $fail('Reservations must start and end on the same day.');
                    }
                },
            ],

            'organisation' => [
                'nullable',
                'exists:organisations,id',
                function ($attribute, $value, $fail) use ($user) {
                    if ($value && ! $user->organisations->contains($value)) {
                        $fail('You can only use organisations you are a member of.');
                    }
                },
            ],
        ];
    }
}
