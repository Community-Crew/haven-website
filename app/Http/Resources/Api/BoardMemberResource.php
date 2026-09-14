<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps a Membership that holds a public board position - see
 * BoardIndexController for the query this is fed by.
 */
class BoardMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->user->name,
            'title' => $this->boardPosition->name,
            // null for a global position (Voorzitter, Secretaris, ...);
            // set to the commission's name for a commission-scoped one, so
            // the frontend can group commission board members together.
            'organisation' => $this->boardPosition->organisation?->name,
        ];
    }
}
