<?php

namespace App\Http\Requests\Api\User;

use App\Enums\ReservationStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * @queryParam status string Filter by reservation state (pending, confirmed, cancelled). Example: confirmed
 * @queryParam room_slug string Filter reservations strictly linked to a specific room slug identifier. Example: deluxe-studio-suite
 * @queryParam start_date string The starting boundary date (YYYY-MM-DD). Defaults to today if omitted. Example: 2026-06-07
 * @queryParam end_date string The ending boundary date limit (YYYY-MM-DD). Filters indefinitely if omitted. Example: 2026-06-30
 * @queryParam page integer The pagination page chunk number wrapper. Example: 1
 */
class ReservationIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', new Enum(ReservationStatus::class)],
            'room_slug' => ['nullable', 'string', 'alpha_dash'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
