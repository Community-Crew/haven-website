<?php

namespace App\Http\Requests\Api\Reservation;

use App\Services\ProofOfWork;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreReservationRequest extends FormRequest
{
    /**
     * Seconds the solved challenge had been out - set once it's verified.
     */
    public ?int $challengeAge = null;

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

    /**
     * The proof-of-work solution (see ReservationChallengeController), checked
     * last so a reservation with another mistake doesn't use up the
     * challenge. Errors land on `pow_challenge` with a reason code the app
     * reacts to by fetching and solving a new one.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $proofOfWork = app(ProofOfWork::class);

                if (! $proofOfWork->enabled() || $validator->errors()->isNotEmpty()) {
                    return;
                }

                $result = $proofOfWork->verify($this->user(), $this->input('pow_challenge'), $this->input('pow_nonce'));

                if (! $result->passed()) {
                    $validator->errors()->add('pow_challenge', "proof_of_work_{$result->failure}");

                    return;
                }

                $this->challengeAge = $result->age;
            },
        ];
    }
}
