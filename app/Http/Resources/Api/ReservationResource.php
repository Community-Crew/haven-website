<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ReservationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $isOwner = $this->user_id === Auth::id();
        $canSeeDetails = $isOwner || $this->share_user;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'room_id' => $this->room_id,
            'status' => $this->status->value,
            'is_mine' => $isOwner,

            'user_id' => $this->when($canSeeDetails, $this->user_id),
            'organisation' => $this->when($canSeeDetails && $this->organistation_id, function () {
                return [
                    'id' => $this->organisation->id,
                    'name' => $this->organisation->name,
                    'logo_url' => $this->organisation->image_url,
                ];
            }),

            'checks' => $this->when($isOwner, fn () => ReservationCheckResource::collection($this->checks)),

            // Only ever the subset an admin explicitly flipped visible - see
            // Reservation::visibleToResidentNotes(), which is what the
            // controller eager-loads under this same relation name so an
            // internal note is never even fetched for this endpoint.
            'notes' => $this->when($isOwner, fn () => $this->visibleToResidentNotes->map(fn ($note) => [
                'body' => $note->body,
                'created_at' => $note->created_at,
            ])),
        ];
    }
}
