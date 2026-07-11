<?php

namespace App\Http\Requests\Api\Reservation;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id' => ['required', 'exists:rooms,id'],
            'date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_at' => [
                'required',
                'date_format:H:i',
                function ($attribute, $value, $fail) {
                    $bookingDateTime = Carbon::parse($this->date.' '.$value, 'Europe/Amsterdam');
                    if ($bookingDateTime->isPast()) {
                        $fail('You cannot reserve a timeslot that has already passed.');
                    }
                },
                function ($attribute, $value, $fail) {
                    $requestedStart = $value;
                    $requestedEnd = $this->end_at;

                    $exists = Reservation::where('room_id', $this->room_id)
                        ->where('date', $this->date)
                        ->where(function ($query) use ($requestedStart, $requestedEnd) {
                            $query->where(function ($q) use ($requestedStart) {
                                $q->where('start_at', '<=', $requestedStart)
                                    ->where('end_at', '>', $requestedStart);
                            })->orWhere(function ($q) use ($requestedEnd) {
                                $q->where('start_at', '<', $requestedEnd)
                                    ->where('end_at', '>=', $requestedEnd);
                            })->orWhere(function ($q) use ($requestedStart, $requestedEnd) {
                                $q->where('start_at', '>=', $requestedStart)
                                    ->where('end_at', '<=', $requestedEnd);
                            });
                        })
                        ->when($this->reservation, function ($query) {
                            $query->where('id', '!=', $this->reservation->id);
                        })
                        ->exists();

                    if ($exists) {
                        $fail('This timeslot is already reserved.');
                    }
                },
            ],
            'end_at' => ['required', 'date_format:H:i', 'after:start_at'],
        ];
    }
}
