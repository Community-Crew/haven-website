<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shared by both the check-in/check-out endpoints (which return a single
 * check right after it's submitted) and ReservationResource's checks[]
 * (which lists every check for a reservation), so the shape a resident's
 * app sees is identical either way. flag_reason is deliberately left out
 * here too, on top of ReservationCheck's own $hidden - it's the automated
 * pass's heuristic and only ever meant for the admin review queue.
 */
class ReservationCheckResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->type->value,
            'verification_status' => $this->verification_status->value,
            'occurred_at' => $this->occurred_at,
            'image_urls' => $this->photo_urls,
            'rejection_note' => $this->rejection_note,
        ];
    }
}
