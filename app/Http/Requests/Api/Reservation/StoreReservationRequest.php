<?php

namespace App\Http\Requests\Api\Reservation;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // The frontend sends full "Y-m-d H:i:s" datetimes for start_at/end_at
        // (matching the reservations table's dateTime columns - there is no
        // separate "date" column). Overlap checking is left to
        // ReservationService::ensureNoOverlap(), which already does it
        // correctly against start_at/end_at - duplicating it here against a
        // nonexistent "date" column was the source of the 500.
        return [
            'room_id' => ['required', 'exists:rooms,id'],
            'name' => ['required', 'string', 'max:255'],
            'start_at' => ['required', 'date', 'after_or_equal:now'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'share_name' => ['required', 'boolean'],
        ];
    }
}
